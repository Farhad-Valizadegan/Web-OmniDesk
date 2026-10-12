import sys
import unittest
from pathlib import Path
from unittest.mock import MagicMock, patch

# Unit tests cover protocol/control flow without requiring native TDLib.
sys.modules.setdefault("tdjson", MagicMock())

SPIKE_DIR = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(SPIKE_DIR))

import tdlib_probe  # noqa: E402


class FakeClient:
    def __init__(self, response=None, error=None):
        self.response = response or {"@type": "ok"}
        self.error = error
        self.calls = []
        self.wait_calls = []
        self.chat_lists = set()

    def send(self, payload, tag=None):
        request_tag = tag or f"request-{len(self.calls) + 1}"
        self.calls.append((payload, request_tag))
        return request_tag

    def wait_for_response(self, tag, timeout=15.0, operation="request"):
        self.wait_calls.append((tag, timeout, operation))
        if self.error is not None:
            raise self.error
        return self.response

    def receive(self, timeout=1.0):
        return None


class TdLibProbeTests(unittest.TestCase):
    def test_reader_never_prompts_for_expired_session_credentials(self):
        client = FakeClient()
        client.receive = MagicMock(return_value={"@type": "updateAuthorizationState",
            "authorization_state": {"@type": "authorizationStateWaitCode"}})
        with patch.object(tdlib_probe, "getpass") as prompt:
            with self.assertRaisesRegex(RuntimeError, "requires authorization"):
                tdlib_probe._authorization_loop(client, 1, "test", Path("test"), interactive=False)
            prompt.assert_not_called()

    def test_reader_session_initialization_has_a_deadline(self):
        client = FakeClient()
        with self.assertRaisesRegex(TimeoutError, "initialization timed out"):
            tdlib_probe._authorization_loop(client, 1, "test", Path("test"), interactive=False, timeout=0)

    def test_discovery_stops_before_request_when_shared_budget_expires(self):
        client = FakeClient()
        with self.assertRaises(tdlib_probe.TdLibResponseTimeout):
            tdlib_probe._load_chat_list(client, {"@type": "chatListMain"}, 50, deadline=0)
        self.assertEqual(client.calls, [])

    def test_all_discovery_lists_share_the_same_deadline(self):
        client = FakeClient()
        client.chat_lists = {("chatListFolder", 1)}
        with patch.object(tdlib_probe, "_load_chat_list") as load:
            tdlib_probe._load_main_chats(client)
        self.assertEqual(load.call_count, 3)
        deadlines = [call.kwargs["deadline"] for call in load.call_args_list]
        self.assertEqual(len(set(deadlines)), 1)

    def test_only_chat_discovery_404_is_end_of_list(self):
        client = FakeClient(
            error=tdlib_probe.TdLibRequestError(
                "chat_discovery:chatListMain",
                404,
                "Not Found",
            )
        )

        with patch.object(tdlib_probe, "_drain_updates"):
            tdlib_probe._load_chat_list(
                client,
                {"@type": "chatListMain"},
                max_pages=3,
            )

        self.assertEqual(len(client.calls), 1)

    def test_non_discovery_404_is_not_end_of_list(self):
        client = FakeClient(
            error=tdlib_probe.TdLibRequestError(
                "history_initial",
                404,
                "Not Found",
            )
        )

        with self.assertRaises(tdlib_probe.TdLibRequestError):
            tdlib_probe._get_history(client, chat_id=100)

    def test_non_end_discovery_error_is_not_swallowed(self):
        client = FakeClient(
            error=tdlib_probe.TdLibRequestError(
                "chat_discovery:chatListMain",
                500,
                "Internal error",
            )
        )

        with self.assertRaises(tdlib_probe.TdLibRequestError):
            tdlib_probe._load_chat_list(
                client,
                {"@type": "chatListMain"},
                max_pages=3,
            )

    def test_discovery_is_bounded_by_page_limit(self):
        client = FakeClient()

        with patch.object(tdlib_probe, "_drain_updates"):
            tdlib_probe._load_chat_list(
                client,
                {"@type": "chatListMain"},
                max_pages=2,
            )

        self.assertEqual(len(client.calls), 2)
        self.assertEqual(
            client.wait_calls[0][2],
            "chat_discovery:chatListMain",
        )

    def test_history_timeout_preserves_operation(self):
        client = FakeClient(
            error=tdlib_probe.TdLibResponseTimeout(
                "history_initial",
                "request-1",
            )
        )

        with self.assertRaises(tdlib_probe.TdLibResponseTimeout) as context:
            tdlib_probe._get_history(client, chat_id=100)

        self.assertEqual(context.exception.operation, "history_initial")

    def test_history_request_preserves_selected_chat_and_cursor(self):
        client = FakeClient(response={"@type": "messages", "messages": []})

        tdlib_probe._get_history(
            client,
            chat_id=987654,
            limit=20,
            before_message_id=12345,
        )

        payload, _ = client.calls[0]
        self.assertEqual(payload["@type"], "getChatHistory")
        self.assertEqual(payload["chat_id"], 987654)
        self.assertEqual(payload["from_message_id"], 12345)
        self.assertEqual(
            client.wait_calls[0][2],
            "history_pagination",
        )


if __name__ == "__main__":
    unittest.main()
