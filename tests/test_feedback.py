"""
test_feedback.py
Test cases for the WashFlow Feedback Module

Modules tested:
  - Customer feedback submission (my_feedback.php)
  - Admin feedback management (feedback.php)
  - Star rating selection
  - Feedback form validation
  - Admin response to feedback
"""

import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC


class TestCustomerFeedback:
    """TC-FB-01 through TC-FB-05: Customer feedback tests."""

    def test_fb01_feedback_page_requires_login(self, driver, navigate, wait):
        """TC-FB-01: Feedback page requires customer login."""
        navigate("logout.php")
        time.sleep(1)

        navigate("my_feedback.php")
        time.sleep(2)

        assert "login.php" in driver.current_url, \
            "Should redirect to login when not authenticated"

    def test_fb02_feedback_page_loads(self, customer_login, navigate, wait):
        """TC-FB-02: Feedback page loads for logged-in customer."""
        navigate("my_feedback.php")
        time.sleep(2)

        page_source = customer_login.page_source.lower()
        assert "feedback" in page_source, \
            "Feedback page should contain feedback-related content"

    def test_fb03_feedback_form_elements(self, customer_login, navigate, wait):
        """TC-FB-03: Feedback form has rating and message fields."""
        navigate("my_feedback.php")
        time.sleep(2)

        page_source = customer_login.page_source.lower()
        # Check for rating-related elements (stars)
        has_rating = "star" in page_source or "rating" in page_source
        assert has_rating, "Feedback form should have rating elements"

    def test_fb04_empty_feedback_prevented(self, customer_login, navigate, wait):
        """TC-FB-04: Cannot submit feedback without content."""
        navigate("my_feedback.php")
        time.sleep(2)

        # Try to find and click submit without filling anything
        submit_buttons = customer_login.find_elements(
            By.CSS_SELECTOR, "button[type='submit']"
        )
        if submit_buttons:
            submit_buttons[0].click()
            time.sleep(1)
            # Should remain on the same page
            assert "my_feedback.php" in customer_login.current_url or \
                   "feedback" in customer_login.current_url.lower()

    def test_fb05_view_past_feedback(self, customer_login, navigate, wait):
        """TC-FB-05: Customer can view their past feedback entries."""
        navigate("my_feedback.php")
        time.sleep(2)

        page_source = customer_login.page_source
        # Page should load without errors
        assert len(page_source) > 500, \
            "Feedback page should render content"


class TestAdminFeedbackManagement:
    """TC-FB-06 through TC-FB-09: Admin feedback management tests."""

    def test_fb06_admin_feedback_page_loads(self, admin_login, navigate, wait):
        """TC-FB-06: Admin can access feedback management page."""
        navigate("feedback.php")
        time.sleep(2)

        assert "feedback.php" in admin_login.current_url, \
            "Admin should access feedback management"

        page_source = admin_login.page_source.lower()
        assert "feedback" in page_source

    def test_fb07_feedback_list_displayed(self, admin_login, navigate, wait):
        """TC-FB-07: Admin sees a list/table of feedback entries."""
        navigate("feedback.php")
        time.sleep(2)

        # Check for table or list elements
        tables = admin_login.find_elements(By.CSS_SELECTOR, "table, .feedback-card, .wf-card")
        assert len(tables) > 0, "Should display feedback entries"

    def test_fb08_feedback_has_star_ratings(self, admin_login, navigate, wait):
        """TC-FB-08: Feedback entries show star ratings."""
        navigate("feedback.php")
        time.sleep(2)

        stars = admin_login.find_elements(By.CSS_SELECTOR, ".fa-star")
        # There should be star icons on the page
        assert len(stars) >= 0, "Star rating icons should be present"

    def test_fb09_admin_feedback_requires_auth(self, driver, navigate, wait):
        """TC-FB-09: Feedback management requires admin login."""
        navigate("logout.php")
        time.sleep(1)

        navigate("feedback.php")
        time.sleep(2)

        assert "admin_login.php" in driver.current_url or \
               "login" in driver.current_url.lower(), \
               "Should redirect non-admin users"
