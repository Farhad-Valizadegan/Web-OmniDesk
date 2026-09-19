#!/usr/bin/env python3
from __future__ import annotations

import json
import os
import sys
from pathlib import Path
from typing import Any

ADAPTER_DIR = Path(__file__).resolve().parent
SPIKE_DIR = ADAPTER_DIR.parent

sys.path.insert(0, str(SPIKE_DIR))

from tdlib_probe import (  # noqa: E402
    TdJsonClient,
    _authorization_loop,
    _get_history,
    _load_main_chats,
    _required_env,
    _select_supported_chat,
)

JsonObject = dict[str, Any]


def main() -> int:
    api_id = int(_required_env("TELEGRAM_API_ID"))
    api_hash = _required_env("TELEGRAM_API_HASH")

    runtime_dir = Path(
        os.getenv(
            "TELEGRAM_TDLIB_RUNTIME",
            str(SPIKE_DIR / "runtime"),
        )
    ).resolve()

    db_dir = runtime_dir / "tdlib-db"
    db_dir.mkdir(parents=True, exist_ok=True)

    client = TdJsonClient()

    _authorization_loop(
        client,
        api_id,
        api_hash,
        db_dir,
    )

    _load_main_chats(client)

    chat = _select_supported_chat(client)
    history = _get_history(client, int(chat["id"]))

    messages = history.get("messages") or []

    result: JsonObject = {
        "provider": "telegram",
        "chat": {
            "id": chat.get("id"),
            "type": chat.get("type", {}).get("@type"),
            "title": chat.get("title"),
        },
        "messages": [],
    }

    for message in messages[:10]:
        content = message.get("content") or {}

        if content.get("@type") != "messageText":
            continue

        text_object = content.get("text") or {}
        text = text_object.get("text", "")

        result["messages"].append(
            {
                "id": message.get("id"),
                "chat_id": message.get("chat_id"),
                "date": message.get("date"),
                "sender_id": message.get("sender_id"),
                "text": text,
            }
        )

    print(json.dumps(result, indent=2, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())