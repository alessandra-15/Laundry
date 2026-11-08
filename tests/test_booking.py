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

    # book_service.php is a 4-step wizard. Delivery options, date/time and
    # instructions live on step 3, which is hidden until steps 1-2 are done.
    # The real radio/checkbox inputs are invisible (opacity 0,
    # pointer-events none) behind styled cards, so we tap the cards.

    def _go_to_step3(self, driver, navigate, wait):
        """Pick a service, then click Next twice to reach step 3."""
        navigate("book_service.php")

        # Step 1: tap the first service card
        service_card = wait.until(EC.element_to_be_clickable(
            (By.CSS_SELECTOR, ".wf-service-opt .wf-service-box")
        ))
        service_card.click()
        wait.until(EC.element_located_to_be_selected(
            (By.CSS_SELECTOR, "input[name='service_ids[]']")
        ))
        wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "#wfNextBtn"))).click()

        # Step 2: add-ons are optional, just continue
        wait.until(EC.visibility_of_element_located(
            (By.CSS_SELECTOR, ".wf-step-panel.active[data-step='2']")
        ))
        wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "#wfNextBtn"))).click()

        wait.until(EC.visibility_of_element_located(
            (By.CSS_SELECTOR, ".wf-step-panel.active[data-step='3']")
        ))

    def _select_delivery(self, driver, wait, value):
        """Tap the delivery card for `value` and return its radio input."""
        radio = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, f"input[name='delivery'][value='{value}']")
        ))
        card = radio.find_element(
            By.XPATH, "following-sibling::div[contains(@class, 'wf-delivery-box')]"
        )
        card.click()
        return radio

    def test_bk04_delivery_option_walkin(self, customer_login, navigate, wait):
        """TC-BK-04: Walk-in delivery option is selectable."""
        self._go_to_step3(customer_login, navigate, wait)

        # Pick a different option first so selecting Walk-in is a real change
        # (Walk-in is the default).
        self._select_delivery(customer_login, wait, "delivery")
        walkin = self._select_delivery(customer_login, wait, "walkin")
        assert walkin.is_selected(), "Walk-in option should be selectable"

    def test_bk05_delivery_option_delivery(self, customer_login, navigate, wait):
        """TC-BK-05: Delivery option is selectable."""
        self._go_to_step3(customer_login, navigate, wait)

        delivery = self._select_delivery(customer_login, wait, "delivery")
        assert delivery.is_selected(), "Delivery option should be selectable"

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
        self._go_to_step3(customer_login, navigate, wait)

        instructions = wait.until(EC.visibility_of_element_located(
            (By.CSS_SELECTOR, "textarea[name='instructions']")
        ))
        text = "Please handle with care - delicate fabrics"
        # Scroll into view without tapping (a tap can land on a sticky
        # footer/nav bar on small screens); send_keys focuses the field itself.
        customer_login.execute_script(
            "arguments[0].scrollIntoView({block: 'center', behavior: 'instant'});",
            instructions,
        )
        instructions.clear()
        instructions.send_keys(text)
        # Typed text lives in the DOM *property*, not the HTML attribute.
        # Appium's get_attribute() returns the raw attribute (None for a
        # <textarea>), so read the property instead.
        value = instructions.get_property("value") or ""
        assert text in value, \
            f"Should be able to type in instructions, field contains: {value!r}"


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
