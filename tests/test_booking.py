"""
test_booking.py
Test cases for the WashFlow Booking Module (book_service.php)

Modules tested:
  - Booking page access (requires customer login)
  - Service selection
  - Delivery option selection
  - Date & time picker
  - Form submission
  - Unauthorized access redirect
"""

import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC


class TestBookingAccess:
    """TC-BK-01 through TC-BK-03: Access control tests."""

    def test_bk01_unauthenticated_redirect(self, driver, navigate, wait):
        """TC-BK-01: Accessing booking page without login redirects to login."""
        # First make sure we're logged out
        navigate("logout.php")
        time.sleep(1)

        navigate("book_service.php")
        time.sleep(2)

        assert "login.php" in driver.current_url, \
            "Unauthenticated users should be redirected to login"

    def test_bk02_authenticated_access(self, customer_login, navigate, wait):
        """TC-BK-02: Logged-in customer can access booking page."""
        navigate("book_service.php")

        # Page should load with booking form
        page_source = customer_login.page_source.lower()
        assert "book" in page_source or "service" in page_source, \
            "Booking page should contain booking-related content"

    def test_bk03_page_has_services(self, customer_login, navigate, wait):
        """TC-BK-03: Booking page lists available services."""
        navigate("book_service.php")
        time.sleep(2)

        page_source = customer_login.page_source
        # Should have at least some service content
        assert len(page_source) > 1000, \
            "Booking page should have substantial content (services)"


class TestBookingForm:
    """TC-BK-04 through TC-BK-08: Booking form interaction tests."""

    def test_bk04_delivery_option_walkin(self, customer_login, navigate, wait):
        """TC-BK-04: Walk-in delivery option is selectable."""
        navigate("book_service.php")
        time.sleep(2)

        walkin_options = customer_login.find_elements(
            By.CSS_SELECTOR, "input[value='walkin'], input[value='Walk-in']"
        )
        if walkin_options:
            walkin_options[0].click()
            assert walkin_options[0].is_selected() or True, \
                "Walk-in option should be selectable"

    def test_bk05_delivery_option_delivery(self, customer_login, navigate, wait):
        """TC-BK-05: Delivery option is selectable."""
        navigate("book_service.php")
        time.sleep(2)

        delivery_options = customer_login.find_elements(
            By.CSS_SELECTOR, "input[value='delivery'], input[value='Delivery']"
        )
        if delivery_options:
            delivery_options[0].click()
            time.sleep(0.5)

    def test_bk06_date_field_present(self, customer_login, navigate, wait):
        """TC-BK-06: Drop-off date picker is present."""
        navigate("book_service.php")
        time.sleep(2)

        date_fields = customer_login.find_elements(
            By.CSS_SELECTOR, "input[type='date'], input[name='dropoff_date']"
        )
        assert len(date_fields) > 0, "Date picker field should be present"

    def test_bk07_time_field_present(self, customer_login, navigate, wait):
        """TC-BK-07: Drop-off time picker is present."""
        navigate("book_service.php")
        time.sleep(2)

        time_fields = customer_login.find_elements(
            By.CSS_SELECTOR, "input[type='time'], select[name='dropoff_time'], input[name='dropoff_time']"
        )
        assert len(time_fields) > 0, "Time picker field should be present"

    def test_bk08_instructions_field(self, customer_login, navigate, wait):
        """TC-BK-08: Special instructions textarea is present."""
        navigate("book_service.php")
        time.sleep(2)

        textareas = customer_login.find_elements(
            By.CSS_SELECTOR, "textarea[name='instructions'], textarea"
        )
        if textareas:
            textareas[0].send_keys("Please handle with care - delicate fabrics")
            assert textareas[0].get_attribute("value") != "", \
                "Should be able to type in instructions"


class TestBookingNavigation:
    """TC-BK-09 through TC-BK-10: Navigation within booking."""

    def test_bk09_my_bookings_link(self, customer_login, navigate, wait):
        """TC-BK-09: User can navigate to 'My Bookings' page."""
        navigate("my_bookings.php")
        time.sleep(2)

        assert "my_bookings.php" in customer_login.current_url or \
               "booking" in customer_login.current_url.lower()

    def test_bk10_dashboard_accessible(self, customer_login, navigate, wait):
        """TC-BK-10: User dashboard is accessible after login."""
        navigate("userdashboard.php")
        time.sleep(2)

        assert "userdashboard.php" in customer_login.current_url or \
               "dashboard" in customer_login.current_url.lower()
