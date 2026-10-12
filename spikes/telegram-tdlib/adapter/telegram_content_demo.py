#!/usr/bin/env python3
from __future__ import annotations

import base64
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
    TdLibResponseTimeout,
    _authorization_loop,
    _get_chat,
    _get_history,
    _load_main_chats,
    _open_chat,
    _required_env,
)

JsonObject = dict[str, Any]
SUPPORTED_CHAT_TYPES = {
    "chatTypePrivate": "private",
    "chatTypeBasicGroup": "group",
}


def _chat_photo(chat: JsonObject) -> JsonObject | None:
    thumbnail = ((chat.get("photo") or {}).get("minithumbnail") or {})
    encoded = thumbnail.get("data")
    if not isinstance(encoded, str) or not encoded or len(encoded) > 100_000:
        return None
    try:
        decoded = base64.b64decode(encoded, validate=True)
    except (ValueError, base64.binascii.Error):
        return None
    if len(decoded) > 64 * 1024:
        return None
    if decoded.startswith(b"\xff\xd8\xff"):
        mime_type = "image/jpeg"
    elif decoded.startswith(b"\x89PNG\r\n\x1a\n"):
        mime_type = "image/png"
    elif decoded.startswith(b"RIFF") and decoded[8:12] == b"WEBP":
        mime_type = "image/webp"
    else:
        return None
    return {"mime_type": mime_type, "data": encoded}


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

    chat_id = chat.get("id")
    if not isinstance(chat_id, int):
        return None

    return {
        "id": chat_id,
        "type": normalized_type,
        "tdlib_type": chat_type,
        "title": chat.get("title") or "Untitled chat",
        "photo": _chat_photo(chat),
        "unread_count": int(chat.get("unread_count") or 0),
        "last_message": _chat_preview(chat),
        "last_message_date": (
            (chat.get("last_message") or {}).get("date")
        ),
    }


def _get_bounded_history(
    client: TdJsonClient,
    chat_id: int,
    limit: int,
    before_message_id: int,
) -> list[JsonObject]:
    requested_limit = max(1, min(limit, 20))
    # TDLib may return a short page even when more history exists. Deliver it
    # immediately; filling the page can discard good messages on a later timeout.
    history = _get_history(
        client, chat_id, limit=requested_limit,
        before_message_id=before_message_id,
    )
    messages: list[JsonObject] = []
    seen: set[int] = set()
    for message in history.get("messages") or []:
        message_id = message.get("id")
        if not isinstance(message_id, int) or message_id in seen:
            continue
        if before_message_id and message_id >= before_message_id:
            continue
        seen.add(message_id)
        messages.append(message)
    return messages[:requested_limit]


def _media_file(message: JsonObject) -> tuple[str, JsonObject] | None:
    content = message.get("content") or {}
    if content.get("is_secret") or message.get("self_destruct_type"):
        return None
    photo = None
    if content.get("@type") == "messagePhoto":
        photo = content.get("photo")
    elif content.get("@type") == "messageText":
        # Article previews are text messages with their own Telegram-hosted
        # photo, not messagePhoto attachments. Use TDLib, never fetch the URL.
        preview_type = (content.get("link_preview") or {}).get("type") or {}
        photo = preview_type.get("photo")
    sizes = [size for size in (photo or {}).get("sizes", [])
             if isinstance((size.get("photo") or {}).get("id"), int)]
    if sizes:
        size = max(sizes, key=lambda item: item.get("width", 0) * item.get("height", 0))
        return "photo", size["photo"]
    if content.get("@type") == "messageVideo":
        video = (content.get("video") or {}).get("video")
        if video:
            return "video", video
    if content.get("@type") == "messageVoiceNote":
        voice = (content.get("voice_note") or {}).get("voice")
        if voice:
            return "voice", voice
    return None


def _download_media(client: TdJsonClient, chat_id: int, message_id: int) -> JsonObject:
    tag = client.send({"@type": "getMessage", "chat_id": chat_id, "message_id": message_id})
    message = client.wait_for_response(tag, timeout=15, operation="media_message")
    media = _media_file(message)
    if media is None:
        raise RuntimeError("This message has no supported photo, video, or voice recording.")
    kind, file = media
    maximum = 100 * 1024 * 1024
    if max(file.get("size", 0), file.get("expected_size", 0)) > maximum:
        raise RuntimeError("Media exceeds the 100 MB preview limit.")
    tag = client.send({"@type": "downloadFile", "file_id": file["id"],
                       "priority": 16, "offset": 0, "limit": maximum + 1, "synchronous": True})
    downloaded = client.wait_for_response(tag, timeout=40, operation="media_download")
    local = downloaded.get("local") or {}
    path = Path(local.get("path") or "")
    if not local.get("is_downloading_completed") or not path.is_file():
        raise RuntimeError("Media download is incomplete. Try again.")
    if path.stat().st_size > maximum:
        raise RuntimeError("Media exceeds the 100 MB preview limit.")
    return {"media_path": str(path.resolve()), "kind": kind}


def _message_summary(message: JsonObject) -> JsonObject:
    media = _media_file(message)
    return {
        "id": message.get("id"),
        "chat_id": message.get("chat_id"),
        "date": message.get("date"),
        "sender_id": message.get("sender_id"),
        "text": _message_text(message),
        "media": {"kind": media[0], "spoiler": bool((message.get("content") or {}).get("has_spoiler"))} if media else None,
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

    requested_chat_raw = os.getenv("TELEGRAM_CHAT_ID", "").strip()
    requested_chat_id: int | None = None

    if requested_chat_raw:
        try:
            requested_chat_id = int(requested_chat_raw)
        except ValueError as exc:
            raise RuntimeError(
                "TELEGRAM_CHAT_ID must be an integer."
            ) from exc

    client = TdJsonClient()
    requested_chat: JsonObject | None = None
    warnings: list[str] = []

    with contextlib.redirect_stdout(io.StringIO()):
        _authorization_loop(client, api_id, api_hash, db_dir, interactive=False)

        media_message_id = int(os.getenv("TELEGRAM_MEDIA_MESSAGE_ID", "0") or "0")
        if media_message_id:
            if requested_chat_id is None:
                raise RuntimeError("A chat is required to load media.")
            media_result = _download_media(client, requested_chat_id, media_message_id)
        else:
            media_result = None

        if media_result is not None:
            pass
        elif requested_chat_id is None:
            try:
                _load_main_chats(client)
            except TdLibResponseTimeout:
                if not client.chats:
                    raise
                warnings.append("Chat discovery took too long. Showing the chats loaded so far.")
        else:
            requested_chat = _get_chat(client, requested_chat_id)

    if media_result is not None:
        print(json.dumps(media_result))
        return 0

    if requested_chat_id is None:
        discovered_chats = [
            chat
            for chat in client.chats.values()
            if (chat.get("type") or {}).get("@type") in {
                "chatTypePrivate",
                "chatTypeBasicGroup",
                "chatTypeSupergroup",
            }
        ]
    else:
        discovered_chats = [requested_chat] if requested_chat else []

    discovered_chats.sort(
        key=lambda chat: (
            int((chat.get("last_message") or {}).get("date") or 0),
            int(chat.get("id") or 0),
        ),
        reverse=True,
    )

    chats = [
        summary
        for chat in discovered_chats
        if (summary := _chat_summary(chat)) is not None
    ]

    if not chats:
        raise RuntimeError("No supported Telegram chats were discovered.")

    selected_chat = next(
        (
            chat
            for chat in chats
            if requested_chat_id is not None
            and chat["id"] == requested_chat_id
        ),
        None,
    )

    if selected_chat is None:
        if requested_chat_id is not None:
            raise RuntimeError(
                "Requested Telegram chat was not found in the discovered lists."
            )

        selected_chat = chats[0]

    try:
        requested_limit = int(
            os.getenv("TELEGRAM_MESSAGE_LIMIT", "20")
        )
    except ValueError as exc:
        raise RuntimeError(
            "TELEGRAM_MESSAGE_LIMIT must be an integer."
        ) from exc

    requested_limit = max(1, min(requested_limit, 20))

    try:
        before_message_id = int(
            os.getenv("TELEGRAM_BEFORE_MESSAGE_ID", "0") or "0"
        )
    except ValueError as exc:
        raise RuntimeError(
            "TELEGRAM_BEFORE_MESSAGE_ID must be an integer."
        ) from exc

    messages: list[JsonObject] = []

    if requested_chat_id is not None:
        _open_chat(client, int(selected_chat["id"]))
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
        if messages
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
        "warnings": warnings,
    }

    print(json.dumps(result, indent=2, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
