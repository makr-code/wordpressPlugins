from create_wordpress_pages import build_menu_item_plan


def test_build_menu_item_plan_includes_docs_children():
    pages = [
        {
            "title": "Docs",
            "slug": "docs",
            "menu_order": 20,
            "parent": None,
            "children": [
                {"title": "Getting Started", "slug": "docs/getting-started", "menu_order": 10, "parent": "docs"},
                {"title": "API Reference", "slug": "docs/api", "menu_order": 20, "parent": "docs"},
            ],
        },
        {"title": "Downloads", "slug": "downloads", "menu_order": 30, "parent": None},
    ]

    plan = build_menu_item_plan(pages)

    assert {item["title"] for item in plan} == {"Docs", "Getting Started", "API Reference", "Downloads"}
    assert plan[0]["parent"] is None
    assert plan[1]["parent"] == "docs"
    assert plan[2]["parent"] == "docs"
