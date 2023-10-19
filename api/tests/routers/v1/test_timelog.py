from decouple import config
from fastapi.testclient import TestClient
from typing import Dict

API_BASE_URL = config("API_BASE_URL")


def test_get_task_types_authenticated(client: TestClient, get_regular_user_token_headers: Dict[str, str]) -> None:
    expected_types = [
        {"slug": "meeting", "name": "Meeting", "active": True},
        {"slug": "deprecated", "name": "Deprecated Type", "active": False},
    ]

    response = client.get(
        f"{API_BASE_URL}/v1/timelog/task_types/",
        headers=get_regular_user_token_headers,
    )
    assert response.status_code == 200
    task_types = response.json()
    assert task_types == expected_types


def test_get_user_cannot_get_templates_from_other_user(
    client: TestClient, get_regular_user_token_headers: Dict[str, str]
) -> None:
    response = client.get(
        f"{API_BASE_URL}/v1/timelog/templates/", headers=get_regular_user_token_headers, params={"user_id": 2}
    )
    assert response.status_code == 403
    content = response.json()
    assert content["detail"] == "You are not authorized to see templates for this user"


def test_get_user_and_global_templates(client: TestClient, get_regular_user_token_headers: Dict[str, str]) -> None:
    expected_templates = [
        {
            "id": 1,
            "name": "Coffee Break",
            "story": "coffee",
            "description": "Need to recharge",
            "task_type": "meeting",
            "start_time": "0:00",
            "end_time": "7:00",
            "user_id": 1,
            "is_global": False,
            "project_id": None,
        },
        {
            "id": 3,
            "name": "Working at night",
            "story": None,
            "description": "Working late",
            "task_type": "meeting",
            "start_time": "20:00",
            "end_time": "22:00",
            "user_id": None,
            "is_global": True,
            "project_id": None,
        },
    ]

    response = client.get(
        f"{API_BASE_URL}/v1/timelog/templates/", headers=get_regular_user_token_headers, params={"user_id": 1}
    )
    assert response.status_code == 200
    templates = response.json()
    assert len(templates) == 2
    assert templates == expected_templates