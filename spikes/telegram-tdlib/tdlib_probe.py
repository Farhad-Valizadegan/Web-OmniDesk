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


class TdLibRequestError(RuntimeError):
    def __init__(
        self,
        operation: str,
        code: int | None,
        message: str,
    ) -> None:
        self.operation = operation
        self.code = code
        self.tdlib_message = message
        super().__init__(
            "TDLib request failed "
            f"operation={operation} code={code} message={message}"
        )

    @property
    def is_end_of_list(self) -> bool:
        return (
            self.operation.startswith("chat_discovery:")
            and self.code == 404
        )


class TdLibResponseTimeout(TimeoutError):
    def __init__(self, operation: str, tag: str) -> None:
        self.operation = operation
        self.tag = tag
        super().__init__(
            "Timed out waiting for TDLib response "
            f"operation={operation} tag={tag}"
        )


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
        self.chat_lists: set[tuple[str, int | None]] = set()

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
        event_type = event.get("@type")

        if event_type == "updateNewChat":
            chat = event.get("chat")

            if isinstance(chat, dict) and isinstance(chat.get("id"), int):
                self.chats[chat["id"]] = chat
            return

        chat_id = event.get("chat_id")
        if not isinstance(chat_id, int) or chat_id not in self.chats:
            if event_type != "updateChatAddedToList":
                return

        if event_type == "updateChatAddedToList":
            chat_list = event.get("chat_list") or {}
            list_type = chat_list.get("@type")
            folder_id = chat_list.get("chat_folder_id")
            if isinstance(list_type, str):
                self.chat_lists.add(
                    (
                        list_type,
                        folder_id if isinstance(folder_id, int) else None,
                    )
                )
            return

        chat = self.chats[chat_id]

        if event_type == "updateChatTitle":
            title = event.get("title")
            if isinstance(title, str):
                chat["title"] = title
        elif event_type == "updateChatLastMessage":
            last_message = event.get("last_message")
            if isinstance(last_message, dict):
                chat["last_message"] = last_message
        elif event_type == "updateChatUnreadCount":
            unread_count = event.get("unread_count")
            if isinstance(unread_count, int):
                chat["unread_count"] = unread_count
        elif event_type == "updateChatReadInbox":
            unread_count = event.get("unread_count")
            if isinstance(unread_count, int):
                chat["unread_count"] = unread_count

    def wait_for_response(
        self,
        tag: str,
        timeout: float = 15.0,
        operation: str = "request",
    ) -> JsonObject:
        deadline = time.monotonic() + timeout

        while time.monotonic() < deadline:
            event = self.receive(
                min(1.0, max(0.0, deadline - time.monotonic()))
            )

            if event and event.get("@extra") == tag:
                if event.get("@type") == "error":
                    raw_code = event.get("code")
                    try:
                        code = int(raw_code) if raw_code is not None else None
                    except (TypeError, ValueError):
                        code = None

                    raise TdLibRequestError(
                        operation,
                        code,
                        str(event.get("message") or "Unknown TDLib error"),
                    )

                return event

        raise TdLibResponseTimeout(operation, tag)


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


def _drain_updates(
    client: TdJsonClient,
    max_wait: float = 1.5,
    quiet_period: float = 0.35,
) -> None:
    deadline = time.monotonic() + max_wait
    quiet_deadline = time.monotonic() + quiet_period

    while time.monotonic() < deadline:
        event = client.receive(
            min(0.25, max(0.0, deadline - time.monotonic()))
        )

        if event:
            quiet_deadline = min(
                deadline,
                time.monotonic() + quiet_period,
            )
        elif time.monotonic() >= quiet_deadline:
            return


def _load_chat_list(
    client: TdJsonClient,
    chat_list: JsonObject,
    max_pages: int,
) -> None:
    list_type = str(chat_list.get("@type") or "unknown")

    for page in range(1, max_pages + 1):
        tag = client.send(
            {
                "@type": "loadChats",
                "chat_list": chat_list,
                "limit": 100,
            },
            tag=f"chat-discovery-{list_type}-{page}",
        )

        try:
            client.wait_for_response(
                tag,
                timeout=15.0,
                operation=f"chat_discovery:{list_type}",
            )
        except TdLibRequestError as exc:
            # TDLib explicitly uses 404 when this chat list is exhausted.
            if exc.is_end_of_list:
                return
            raise

        # loadChats returns before all updateNewChat updates have necessarily
        # been observed. Drain a short quiet window before requesting the next
        # bounded page, without using an idle count as a false end condition.
        _drain_updates(client)


def _load_main_chats(client: TdJsonClient) -> None:
    """Load Main, Archive, and discovered Telegram folders incrementally."""
    try:
        max_pages = int(
            os.getenv("TELEGRAM_CHAT_DISCOVERY_PAGES", "50")
        )
    except ValueError as exc:
        raise RuntimeError(
            "TELEGRAM_CHAT_DISCOVERY_PAGES must be an integer."
        ) from exc

    max_pages = max(1, min(max_pages, 100))

    _load_chat_list(
        client,
        {"@type": "chatListMain"},
        max_pages,
    )
    _load_chat_list(
        client,
        {"@type": "chatListArchive"},
        max_pages,
    )

    folder_lists = sorted(
        (
            (list_type, folder_id)
            for list_type, folder_id in client.chat_lists
            if list_type == "chatListFolder" and folder_id is not None
        ),
        key=lambda item: item[1],
    )

    for _, folder_id in folder_lists:
        _load_chat_list(
            client,
            {
                "@type": "chatListFolder",
                "chat_folder_id": folder_id,
            },
            max_pages,
        )


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


def _open_chat(client: TdJsonClient, chat_id: int) -> None:
    tag = client.send(
        {
            "@type": "openChat",
            "chat_id": chat_id,
        }
    )
    client.wait_for_response(
        tag,
        timeout=10.0,
        operation="chat_open",
    )


def _get_history(
    client: TdJsonClient,
    chat_id: int,
    limit: int = 20,
    before_message_id: int = 0,
    only_local: bool = False,
) -> JsonObject:
    bounded_limit = max(1, min(limit, 20))

    try:
        timeout = float(
            os.getenv("TELEGRAM_HISTORY_TIMEOUT_SECONDS", "30")
        )
    except ValueError as exc:
        raise RuntimeError(
            "TELEGRAM_HISTORY_TIMEOUT_SECONDS must be numeric."
        ) from exc

    timeout = max(5.0, min(timeout, 120.0))
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

    return client.wait_for_response(
        tag,
        timeout=timeout,
        operation="history_pagination"
        if before_message_id
        else "history_initial",
    )


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
        _open_chat(client, int(chat["id"]))
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
