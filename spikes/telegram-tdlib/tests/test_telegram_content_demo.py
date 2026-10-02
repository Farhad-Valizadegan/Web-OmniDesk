import sys
import base64
import unittest
from pathlib import Path
from unittest.mock import MagicMock, patch

sys.modules.setdefault("tdjson", MagicMock())
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "adapter"))
import telegram_content_demo as adapter


class ContentHistoryTests(unittest.TestCase):
    def test_chat_summary_exposes_only_a_valid_profile_thumbnail(self):
        encoded = base64.b64encode(b"\xff\xd8\xffthumbnail").decode()
        chat = {"id": 1, "title": "Photo chat", "type": {"@type": "chatTypePrivate"},
                "photo": {"minithumbnail": {"data": encoded},
                          "small": {"local": {"path": "private-path"}}}}
        summary = adapter._chat_summary(chat)
        self.assertEqual(summary["photo"], {"mime_type": "image/jpeg", "data": encoded})
        self.assertNotIn("private-path", str(summary))

    def test_chat_summary_rejects_invalid_profile_thumbnail_bytes(self):
        encoded = base64.b64encode(b"not-an-image").decode()
        chat = {"id": 1, "title": "No photo", "type": {"@type": "chatTypePrivate"},
                "photo": {"minithumbnail": {"data": encoded}}}
        self.assertIsNone(adapter._chat_summary(chat)["photo"])

    def test_voice_recording_is_available_without_exposing_its_local_path(self):
        message = {"content": {"@type": "messageVoiceNote", "voice_note": {
            "voice": {"id": 51, "local": {"path": "private-recording"}}
        }}}
        self.assertEqual(adapter._media_file(message)[0], "voice")
        summary = adapter._message_summary(message)
        self.assertEqual(summary["media"]["kind"], "voice")
        self.assertNotIn("private-recording", str(summary))
        message["content"]["is_secret"] = True
        self.assertIsNone(adapter._media_file(message))

    def test_article_link_preview_photo_is_exposed_as_message_media(self):
        message = {"content": {"@type": "messageText", "text": {"text": "Article link"},
                   "link_preview": {"type": {"@type": "linkPreviewTypeArticle",
                       "photo": {"sizes": [{"width": 800, "height": 600, "photo": {"id": 42}}]}}}}}
        self.assertEqual(adapter._media_file(message), ("photo", {"id": 42}))
        self.assertEqual(adapter._message_summary(message)["text"], "Article link")
        self.assertEqual(adapter._message_summary(message)["media"]["kind"], "photo")

    def test_text_without_preview_photo_stays_text_only(self):
        message = {"content": {"@type": "messageText", "text": {"text": "Text"},
                   "link_preview": {"type": {"@type": "linkPreviewTypeArticle", "photo": None}}}}
        self.assertIsNone(adapter._media_file(message))

    def test_photo_summary_selects_largest_size_without_exposing_paths(self):
        message = {"id": 1, "content": {"@type": "messagePhoto", "caption": {"text": "Caption"},
                   "photo": {"sizes": [
                       {"width": 10, "height": 10, "photo": {"id": 2}},
                       {"width": 800, "height": 600, "photo": {"id": 3, "local": {"path": "private"}}},
                   ]}}}
        self.assertEqual(adapter._media_file(message)[1]["id"], 3)
        summary = adapter._message_summary(message)
        self.assertEqual(summary["media"], {"kind": "photo", "spoiler": False})
        self.assertEqual(summary["text"], "Caption")
        self.assertNotIn("private", str(summary))

    def test_video_summary_and_secret_media(self):
        message = {"content": {"@type": "messageVideo", "video": {"video": {"id": 4}}}}
        self.assertEqual(adapter._message_summary(message)["media"]["kind"], "video")
        message["content"]["is_secret"] = True
        self.assertIsNone(adapter._media_file(message))

    def test_download_requires_message_media_and_rejects_oversized_files(self):
        client = MagicMock()
        client.wait_for_response.return_value = {"content": {"@type": "messageVideo",
            "video": {"video": {"id": 4, "size": 101 * 1024 * 1024}}}}
        with self.assertRaisesRegex(RuntimeError, "100 MB"):
            adapter._download_media(client, 1, 2)
        self.assertEqual(client.send.call_count, 1)

    def test_short_page_returns_without_requesting_more_history(self):
        first = [{"id": 100}, {"id": 90}]
        with patch.object(adapter, "_get_history", side_effect=[
            {"messages": first}, TimeoutError("Older history unavailable")
        ]) as get_history:
            self.assertEqual(adapter._get_bounded_history(object(), 1, 20, 0), first)
            self.assertEqual(get_history.call_count, 1)

    def test_page_removes_inclusive_cursor_and_duplicate_messages(self):
        with patch.object(adapter, "_get_history", return_value={
            "messages": [{"id": 100}, {"id": 90}, {"id": 90}, {"id": 80}]
        }):
            self.assertEqual(adapter._get_bounded_history(object(), 1, 20, 100),
                             [{"id": 90}, {"id": 80}])

    def test_initial_failure_is_not_disguised_as_an_empty_chat(self):
        with patch.object(adapter, "_get_history", side_effect=TimeoutError("Unavailable")):
            with self.assertRaises(TimeoutError):
                adapter._get_bounded_history(object(), 1, 20, 0)


if __name__ == "__main__":
    unittest.main()
