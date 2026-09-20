#!/usr/bin/env python3
from __future__ import annotations

import hashlib
import json
import os
import sys
import time
import tdjson
from getpass import getpass
from pathlib import Path
from typing import Any

JsonObject = dict[str, Any]


def _required_env(name: str) -> str:
    value = os.getenv(name, "").strip()
    if not value:
        raise RuntimeError(f"Missing required environment variable: {name}")
    return value


def _stable_fingerprint(value: int | str) -> str:
    return hashlib.sha256(str(value).encode("utf-8")).hexdigest()[:16]


class TdJsonClient:
    def __init__(self) -> None:
        tdjson.td_execute(
            json.dumps(
                {
                    "@type": "setLogVerbosityLevel",
                    "new_verbosity_level": 0,
                }
            ).encode("utf-8")
        )

        self.client_id = tdjson.td_create_client_id()
        self._request_counter = 0
        self.chats: dict[int, JsonObject] = {}

    def execute(self, query: JsonObject) -> JsonObject | None:
        raw = tdjson.td_execute(json.dumps(query).encode("utf-8"))
        return json.loads(raw.decode("utf-8")) if raw else None

    def send(self, query: JsonObject, tag: str | None = None) -> str:
        if tag is None:
            self._request_counter += 1
            tag = f"req-{self._request_counter}"

        payload = dict(query)
        payload["@extra"] = tag

        tdjson.td_send(
            self.client_id,
            json.dumps(payload).encode("utf-8"),
        )

        return tag

    def receive(self, timeout: float = 1.0) -> JsonObject | None:
        raw = tdjson.td_receive(timeout)

        if not raw:
            return None

        event = json.loads(raw.decode("utf-8"))
        self._observe(event)
        return event

    def _observe(self, event: JsonObject) -> None:
        if event.get("@type") == "updateNewChat":
            chat = event.get("chat")

            if isinstance(chat, dict) and isinstance(chat.get("id"), int):
                self.chats[chat["id"]] = chat

    def wait_for_response(
        self,
        tag: str,
        timeout: float = 15.0,
    ) -> JsonObject:
        deadline = time.monotonic() + timeout

        while time.monotonic() < deadline:
            event = self.receive(
                min(1.0, max(0.0, deadline - time.monotonic()))
            )

            if event and event.get("@extra") == tag:
                if event.get("@type") == "error":
                    raise RuntimeError(
                        "TDLib request failed: "
                        f"code={event.get('code')} "
                        f"message={event.get('message')}"
                    )

                return event

        raise TimeoutError(
            f"Timed out waiting for TDLib response {tag}"
        )


def _authorization_loop(
    client: TdJsonClient,
    api_id: int,
    api_hash: str,
    db_dir: Path,
) -> None:
    client.send(
        {
            "@type": "getOption",
            "name": "version",
        },
        tag="bootstrap",
    )

    while True:
        event = client.receive(1.0)

        if not event:
            continue

        if event.get("@type") == "error":
            raise RuntimeError(
                "Telegram authorization error: "
                f"code={event.get('code')} "
                f"message={event.get('message')}"
            )

        if event.get("@type") != "updateAuthorizationState":
            continue

        state = event.get("authorization_state", {})
        state_type = state.get("@type")
        print(f"Authorization state: {state_type}", flush=True)
        if state_type == "authorizationStateWaitTdlibParameters":
            client.send(
                {
                    "@type": "setTdlibParameters",
                    "database_directory": str(db_dir),
                    "use_message_database": True,
                    "use_secret_chats": False,
                    "api_id": api_id,
                    "api_hash": api_hash,
                    "system_language_code": "en",
                    "device_model": (
                        "OmniDesk PB-023 validation probe"
                    ),
                    "application_version": "0.1.0-spike",
                }
            )

        elif state_type == "authorizationStateWaitPhoneNumber":
            phone = getpass(
                "Telegram phone number (hidden): "
            ).strip()

            client.send(
                {
                    "@type": "setAuthenticationPhoneNumber",
                    "phone_number": phone,
                }
            )

        elif state_type == "authorizationStateWaitEmailAddress":
            email = getpass(
                "Telegram login email (hidden): "
            ).strip()

            client.send(
                {
                    "@type": "setAuthenticationEmailAddress",
                    "email_address": email,
                }
            )

        elif state_type == "authorizationStateWaitEmailCode":
            code = getpass(
                "Telegram email code (hidden): "
            ).strip()

            client.send(
                {
                    "@type": "checkAuthenticationEmailCode",
                    "code": {
                        "@type": "emailAddressAuthenticationCode",
                        "code": code,
                    },
                }
            )

        elif state_type == "authorizationStateWaitCode":
            code = getpass(
                "Telegram authentication code (hidden): "
            ).strip()

            client.send(
                {
                    "@type": "checkAuthenticationCode",
                    "code": code,
                }
            )

        elif state_type == "authorizationStateWaitPassword":
            password = getpass(
                "Telegram 2FA password (hidden): "
            )

            client.send(
                {
                    "@type": "checkAuthenticationPassword",
                    "password": password,
                }
            )

        elif state_type == "authorizationStateWaitRegistration":
            raise RuntimeError(
                "Probe refuses to register a new Telegram account; "
                "use an existing test account."
            )

        elif state_type == "authorizationStateWaitPremiumPurchase":
            raise RuntimeError(
                "Telegram reports that Premium is required "
                "for this authorization flow."
            )

        elif state_type == "authorizationStateReady":
            return

        elif state_type in {
            "authorizationStateClosing",
            "authorizationStateClosed",
        }:
            raise RuntimeError(
                "TDLib authorization ended unexpectedly: "
                f"{state_type}"
            )


def _load_main_chats(client: TdJsonClient) -> None:
    tag = client.send(
        {
            "@type": "loadChats",
            "chat_list": {
                "@type": "chatListMain",
            },
            "limit": 20,
        }
    )

    client.wait_for_response(tag, timeout=20.0)

    deadline = time.monotonic() + 5.0

    while time.monotonic() < deadline:
        client.receive(0.5)


def _select_supported_chat(client: TdJsonClient) -> JsonObject:
    supported_types = {
        "chatTypePrivate",
        "chatTypeBasicGroup",
        "chatTypeSupergroup",
    }

    for chat in client.chats.values():
        chat_type = chat.get("type", {}).get("@type")

        if chat_type in supported_types:
            return chat

    raise RuntimeError(
        "No supported non-secret chat was discovered by the probe."
    )


def _get_history(
    client: TdJsonClient,
    chat_id: int,
    limit: int = 20,
    before_message_id: int = 0,
    only_local: bool = False,
) -> JsonObject:
    bounded_limit = max(1, min(limit, 20))
    tag = client.send(
        {
            "@type": "getChatHistory",
            "chat_id": chat_id,
            "from_message_id": before_message_id,
            "offset": 0,
            "limit": bounded_limit,
            "only_local": only_local,
        }
    )

    return client.wait_for_response(tag, timeout=60.0)


def _open_chat(client: TdJsonClient, chat_id: int) -> None:
    tag = client.send(
        {
            "@type": "openChat",
            "chat_id": chat_id,
        }
    )
    client.wait_for_response(tag, timeout=10.0)

def _build_evidence(
    client: TdJsonClient,
    chat: JsonObject,
    history: JsonObject,
) -> JsonObject:
    messages = history.get("messages") or []
    sampled = messages[:3]

    supported_types = {
        "chatTypePrivate",
        "chatTypeBasicGroup",
        "chatTypeSupergroup",
    }

    return {
        "pb": "PB-023",
        "probe_version": "0.1.0",
        "authorization": "ready",
        "chat_list_retrieval": True,
        "supported_chat_count_observed": sum(
            1
            for current_chat in client.chats.values()
            if current_chat.get("type", {}).get("@type")
            in supported_types
        ),
        "sample_chat": {
            "id_fingerprint": _stable_fingerprint(chat["id"]),
            "type": chat.get("type", {}).get("@type"),
        },
        "history_retrieval": (
            history.get("@type") == "messages"
        ),
        "message_count_observed": len(messages),
        "sample_messages": [
            {
                "id_fingerprint": _stable_fingerprint(
                    message.get("id", "missing")
                ),
                "chat_id_matches": (
                    message.get("chat_id") == chat.get("id")
                ),
                "has_timestamp": isinstance(
                    message.get("date"),
                    int,
                ),
                "has_sender_id": isinstance(
                    message.get("sender_id"),
                    dict,
                ),
                "content_type": (
                    message.get("content") or {}
                ).get("@type"),
            }
            for message in sampled
        ],
        "incremental_mechanism": (
            "TDLib update stream, including updateNewMessage"
        ),
        "secrets_or_message_text_recorded": False,
    }


def main() -> int:
    try:
        api_id = int(
            _required_env("TELEGRAM_API_ID")
        )

        api_hash = _required_env(
            "TELEGRAM_API_HASH"
        )

        runtime_dir = Path(
            os.getenv(
                "TELEGRAM_TDLIB_RUNTIME",
                "runtime",
            )
        ).resolve()

        db_dir = runtime_dir / "tdlib-db"
        db_dir.mkdir(
            parents=True,
            exist_ok=True,
        )

        client = TdJsonClient()

        _authorization_loop(
            client,
            api_id,
            api_hash,
            db_dir,
        )

        print(
            "Authorization ready. "
            "Loading a bounded main chat sample..."
        )

        _load_main_chats(client)

        chat = _select_supported_chat(client)

        history = _get_history(
            client,
            int(chat["id"]),
        )

        evidence = _build_evidence(
            client,
            chat,
            history,
        )

        evidence_path = runtime_dir / "evidence.json"

        evidence_path.write_text(
            json.dumps(
                evidence,
                indent=2,
            ),
            encoding="utf-8",
        )

        print(
            "PB-023 probe completed without writing "
            "message text or secrets to evidence."
        )

        print(f"Evidence: {evidence_path}")
        print(json.dumps(evidence, indent=2))

        return 0

    except (
        RuntimeError,
        TimeoutError,
        ValueError,
    ) as exc:
        print(
            f"Probe failed: {exc}",
            file=sys.stderr,
        )
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
