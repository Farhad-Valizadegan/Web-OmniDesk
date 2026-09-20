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
    content_type = content.get("@type", "")

    if content_type == "messageText":
        text_object = content.get("text") or {}
        return str(text_object.get("text") or "").strip()

    caption = content.get("caption") or {}
    caption_text = str(caption.get("text") or "").strip()
    if caption_text:
        return caption_text

    media_labels = {
        "messagePhoto": "Photo",
        "messageVideo": "Video",
        "messageAnimation": "GIF",
        "messageAudio": "Audio",
        "messageVoiceNote": "Voice message",
        "messageVideoNote": "Video message",
        "messageDocument": "Document",
        "messageSticker": "Sticker",
        "messageLocation": "Location",
        "messageContact": "Contact",
        "messagePoll": "Poll",
    }

    return media_labels.get(content_type, "").strip()


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


def _refresh_chat_metadata(
    client: TdJsonClient,
    chats: list[JsonObject],
) -> None:
    """Refresh chat metadata without loading message history."""
    for chat in chats:
        chat_id = chat.get("id")

        if not isinstance(chat_id, int):
            continue

        try:
            refreshed = client.wait_for_response(
                client.send(
                    {
                        "@type": "getChat",
                        "chat_id": chat_id,
                    },
                    tag=f"chat-{chat_id}",
                ),
                timeout=5.0,
            )
        except (RuntimeError, TimeoutError):
            continue

        if refreshed.get("@type") == "chat":
            chat.clear()
            chat.update(refreshed)


def _get_bounded_history(
    client: TdJsonClient,
    chat_id: int,
    limit: int,
    before_message_id: int,
) -> list[JsonObject]:
    requested_limit = max(1, min(limit, 20))
    messages: list[JsonObject] = []
    cursor = before_message_id

    while len(messages) < requested_limit:
        remaining = requested_limit - len(messages)
        history = _get_history(
            client,
            chat_id,
            limit=remaining,
            before_message_id=cursor,
        )
        batch = history.get("messages") or []

        if not batch:
            break

        known_ids = {
            message.get("id")
            for message in messages
            if isinstance(message.get("id"), int)
        }
        new_messages = [
            message
            for message in batch
            if message.get("id") not in known_ids
        ]

        if not new_messages:
            break

        messages.extend(new_messages)

        oldest_id = batch[-1].get("id")
        if not isinstance(oldest_id, int) or oldest_id == cursor:
            break

        cursor = oldest_id

    return messages[:requested_limit]


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

    discovered_chats = [
        chat
        for chat in client.chats.values()
        if (chat.get("type") or {}).get("@type") in {
            "chatTypePrivate",
            "chatTypeBasicGroup",
            "chatTypeSupergroup",
        }
    ][:20]

    _refresh_chat_metadata(client, discovered_chats)

    chats = [
        summary
        for chat in discovered_chats
        if (summary := _chat_summary(chat)) is not None
    ]

    if not chats:
        raise RuntimeError("No supported Telegram chats were discovered.")

    requested_chat_id = os.getenv("TELEGRAM_CHAT_ID", "").strip()
    selected_chat = next(
        (
            chat
            for chat in chats
            if requested_chat_id
            and str(chat["id"]) == requested_chat_id
        ),
        chats[0],
    )

    requested_limit = max(
        1,
        min(
            int(os.getenv("TELEGRAM_MESSAGE_LIMIT", "20")),
            20,
        ),
    )
    before_message_id = int(
        os.getenv("TELEGRAM_BEFORE_MESSAGE_ID", "0") or "0"
    )

    messages = _get_bounded_history(
        client,
        int(selected_chat["id"]),
        limit=requested_limit,
        before_message_id=before_message_id,
    )

    text_messages = [
        message
        for message in messages
        if _message_text(message)
    ]

    if text_messages and before_message_id == 0:
        latest = text_messages[0]
        selected_chat["last_message"] = _message_text(latest)
        selected_chat["last_message_date"] = latest.get("date")

    message_summaries = [
        _message_summary(message)
        for message in messages
        if _message_text(message)
    ]
    next_before_message_id = (
        messages[-1].get("id")
        if len(messages) >= requested_limit and messages
        else None
    )

    selected_chat["messages_count"] = len(message_summaries)

    result: JsonObject = {
        "provider": "telegram",
        "chats": chats,
        "selected_chat": selected_chat,
        "messages": message_summaries,
        "has_more": next_before_message_id is not None,
        "next_before_message_id": next_before_message_id,
    }

    print(json.dumps(result, indent=2, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
