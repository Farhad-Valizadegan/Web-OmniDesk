#!/usr/bin/env python3
from __future__ import annotations

import contextlib
import io
import json
import os
import sys
import time
from pathlib import Path

ADAPTER_DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(ADAPTER_DIR.parent))

from tdlib_probe import TdJsonClient, _required_env  # noqa: E402


def normalized(state: str) -> str:
    return {
        "authorizationStateWaitPhoneNumber": "awaiting_phone",
        "authorizationStateWaitEmailAddress": "awaiting_email",
        "authorizationStateWaitEmailCode": "awaiting_email_code",
        "authorizationStateWaitCode": "awaiting_code",
        "authorizationStateWaitPassword": "awaiting_password",
        "authorizationStateReady": "connected",
        "authorizationStateClosed": "disconnected",
        "authorizationStateClosing": "disconnected",
    }.get(state, "pending")


def main() -> int:
    api_id = int(_required_env("TELEGRAM_API_ID"))
    api_hash = _required_env("TELEGRAM_API_HASH")
    runtime = Path(_required_env("TELEGRAM_TDLIB_RUNTIME")).resolve()
    db_dir = runtime / "tdlib-db"
    db_dir.mkdir(parents=True, exist_ok=True)
    action = os.getenv("TELEGRAM_AUTH_ACTION", "status")
    value = os.getenv("TELEGRAM_AUTH_VALUE", "")
    phone = os.getenv("TELEGRAM_PHONE", "")
    database_key = _required_env("TELEGRAM_DATABASE_KEY")
    client = TdJsonClient()
    deadline = time.monotonic() + 45
    submitted = action == "status"
    phone_submitted = False

    with contextlib.redirect_stdout(io.StringIO()):
        client.send({"@type": "getOption", "name": "version"}, tag="bootstrap")
        while time.monotonic() < deadline:
            event = client.receive(1.0)
            if not event:
                continue
            if event.get("@type") == "error":
                raise RuntimeError(str(event.get("message") or "Telegram authorization failed"))
            if event.get("@type") != "updateAuthorizationState":
                continue
            state = (event.get("authorization_state") or {}).get("@type", "")
            if state == "authorizationStateWaitTdlibParameters":
                client.send({
                    "@type": "setTdlibParameters",
                    "database_directory": str(db_dir),
                    "use_message_database": True,
                    "use_secret_chats": False,
                    "api_id": api_id,
                    "api_hash": api_hash,
                    "system_language_code": "en",
                    "device_model": "OmniDesk",
                    "application_version": "1.0.0-mvp.1",
                })
                continue
            if state == "authorizationStateWaitEncryptionKey":
                client.send({"@type": "checkDatabaseEncryptionKey", "encryption_key": database_key})
                continue
            if action == "logout" and state == "authorizationStateReady" and not submitted:
                client.send({"@type": "logOut"})
                submitted = True
                continue
            if (
                action in {"code", "password"}
                and state == "authorizationStateWaitPhoneNumber"
                and phone
                and not phone_submitted
            ):
                client.send({"@type": "setAuthenticationPhoneNumber", "phone_number": phone})
                phone_submitted = True
                continue
            expected = {
                "phone": "authorizationStateWaitPhoneNumber",
                "code": "authorizationStateWaitCode",
                "password": "authorizationStateWaitPassword",
            }.get(action)
            if expected == state and not submitted:
                request = {
                    "phone": {"@type": "setAuthenticationPhoneNumber", "phone_number": value},
                    "code": {"@type": "checkAuthenticationCode", "code": value},
                    "password": {"@type": "checkAuthenticationPassword", "password": value},
                }[action]
                client.send(request)
                submitted = True
                continue
            if submitted and state != expected:
                sys.__stdout__.write(json.dumps({"status": normalized(state)}) + "\n")
                sys.__stdout__.flush()
                return 0

    raise RuntimeError("Telegram authorization timed out")


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(str(exc), file=sys.stderr)
        raise SystemExit(1)
