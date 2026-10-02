#!/usr/bin/env python3
from __future__ import annotations

import json
import os
import sys
import time
from pathlib import Path

ADAPTER_DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(ADAPTER_DIR.parent))

from tdlib_probe import TdJsonClient, _required_env  # noqa: E402


def atomic_json(path: Path, value: dict) -> None:
    temporary = path.with_suffix(".tmp")
    temporary.write_text(json.dumps(value), encoding="utf-8")
    temporary.replace(path)


def normalize(state: str) -> str:
    return {
        "authorizationStateWaitPhoneNumber": "awaiting_phone",
        "authorizationStateWaitCode": "awaiting_code",
        "authorizationStateWaitPassword": "awaiting_password",
        "authorizationStateReady": "connected",
        "authorizationStateClosing": "disconnected",
        "authorizationStateClosed": "disconnected",
    }.get(state, "pending")


def main() -> int:
    runtime = Path(_required_env("TELEGRAM_TDLIB_RUNTIME")).resolve()
    ipc = runtime / "auth-ipc"
    commands, responses = ipc / "commands", ipc / "responses"
    commands.mkdir(parents=True, exist_ok=True)
    responses.mkdir(parents=True, exist_ok=True)
    state_file = ipc / "state.json"
    client = TdJsonClient()
    client.send({"@type": "getOption", "name": "version"}, tag="bootstrap")
    state = "pending"
    pending: tuple[str, str] | None = None

    while True:
        event = client.receive(0.2)
        if event and event.get("@type") == "error" and pending:
            command_id, _ = pending
            atomic_json(responses / f"{command_id}.json", {
                "status": state,
                "error": str(event.get("message") or "Telegram authorization failed."),
            })
            pending = None
            continue
        if event and event.get("@type") == "updateAuthorizationState":
            raw = (event.get("authorization_state") or {}).get("@type", "")
            if raw == "authorizationStateWaitTdlibParameters":
                client.send({"@type": "setTdlibParameters", "database_directory": str(runtime / "tdlib-db"), "use_message_database": True, "use_secret_chats": False, "api_id": int(_required_env("TELEGRAM_API_ID")), "api_hash": _required_env("TELEGRAM_API_HASH"), "system_language_code": "en", "device_model": "OmniDesk", "application_version": "1.0.0-mvp.1"})
                continue
            if raw == "authorizationStateWaitEncryptionKey":
                client.send({"@type": "checkDatabaseEncryptionKey", "encryption_key": _required_env("TELEGRAM_DATABASE_KEY")})
                continue
            state = normalize(raw)
            atomic_json(state_file, {"status": state, "heartbeat": time.time()})
            if pending:
                command_id, previous = pending
                if state != previous:
                    atomic_json(responses / f"{command_id}.json", {"status": state})
                    pending = None
                    if state in {"connected", "disconnected"}:
                        return 0

        for path in commands.glob("*.json"):
            try:
                command = json.loads(path.read_text(encoding="utf-8"))
                command_id = str(command["id"])
                action, value = command["action"], str(command.get("value", ""))
                request = None
                if action == "phone" and state == "awaiting_phone": request = {"@type": "setAuthenticationPhoneNumber", "phone_number": value}
                elif action == "code" and state == "awaiting_code": request = {"@type": "checkAuthenticationCode", "code": value}
                elif action == "password" and state == "awaiting_password": request = {"@type": "checkAuthenticationPassword", "password": value}
                elif action == "logout" and state == "connected": request = {"@type": "logOut"}
                elif action == "status": atomic_json(responses / f"{command_id}.json", {"status": state})
                elif action == "shutdown":
                    atomic_json(responses / f"{command_id}.json", {"status": state})
                    return 0
                else: atomic_json(responses / f"{command_id}.json", {"status": state, "error": "Action is not valid for the current Telegram state."})
                if request:
                    client.send(request, tag=f"auth-{command_id}")
                    pending = (command_id, state)
            finally:
                path.unlink(missing_ok=True)

        if state != "pending" and (not state_file.exists() or time.time() - state_file.stat().st_mtime > 1):
            atomic_json(state_file, {"status": state, "heartbeat": time.time()})


if __name__ == "__main__":
    raise SystemExit(main())
