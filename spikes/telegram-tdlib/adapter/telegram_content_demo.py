#!/usr/bin/env python3
from __future__ import annotations

import contextlib
import io
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
)

JsonObject = dict[str, Any]
SUPPORTED_CHAT_TYPES = {
    "chatTypePrivate": "private",
    "chatTypeBasicGroup": "group",
}


def _message_text(message: JsonObject) -> str:
    content = message.get("content") or {}
    if content.get("@type") != "messageText":
        return ""

    text_object = content.get("text") or {}
    return str(text_object.get("text") or "")


def _chat_preview(chat: JsonObject) -> str:
    last_message = chat.get("last_message") or {}
    return _message_text(last_message)


def _chat_summary(chat: JsonObject) -> JsonObject | None:
    chat_type = (chat.get("type") or {}).get("@type")
    normalized_type = SUPPORTED_CHAT_TYPES.get(chat_type)

    if chat_type == "chatTypeSupergroup":
        normalized_type = (
            "channel"
            if (chat.get("type") or {}).get("is_channel")
            else "group"
        )

    if normalized_type is None:
        return None

    return {
        "id": chat.get("id"),
        "type": normalized_type,
        "tdlib_type": chat_type,
        "title": chat.get("title") or "Untitled chat",
        "unread_count": int(chat.get("unread_count") or 0),
        "last_message": _chat_preview(chat),
        "last_message_date": (
            (chat.get("last_message") or {}).get("date")
        ),
    }


def _message_summary(message: JsonObject) -> JsonObject:
    return {
        "id": message.get("id"),
        "chat_id": message.get("chat_id"),
        "date": message.get("date"),
        "sender_id": message.get("sender_id"),
        "text": _message_text(message),
    }


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

    with contextlib.redirect_stdout(io.StringIO()):
        _authorization_loop(client, api_id, api_hash, db_dir)
        _load_main_chats(client)

    chats = [
        summary
        for chat in client.chats.values()
        if (summary := _chat_summary(chat)) is not None
    ][:20]

    if not chats:
        raise RuntimeError("No supported Telegram chats were discovered.")

    selected_chat = chats[0]
    history = _get_history(client, int(selected_chat["id"]))
    messages = history.get("messages") or []

    selected_chat["messages_count"] = len(messages)

    result: JsonObject = {
        "provider": "telegram",
        "chats": chats,
        "selected_chat": selected_chat,
        "messages": [
            _message_summary(message)
            for message in messages[:30]
            if _message_text(message)
        ],
    }

    print(json.dumps(result, indent=2, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
