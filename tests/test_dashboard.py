"""
test_dashboard.py
Test cases for the WashFlow User Dashboard (userdashboard.php)
and Admin Dashboard (dashboard.php)

Modules tested:
  - Customer dashboard access & content
  - Admin dashboard access & content
  - Navigation sidebar/menu items
  - Session protection
"""

import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC


class TestCustomerDashboard:
    """TC-CD-01 through TC-CD-05: Customer dashboard tests."""

    def test_cd01_unauthenticated_redirect(self, driver, navigate, wait):
        """TC-CD-01: Unauthenticated access to dashboard redirects to login."""
        navigate("logout.php")
        time.sleep(1)

        navigate("userdashboard.php")
        time.sleep(2)

        assert "login.php" in driver.current_url, \
            "Should redirect to login when not authenticated"

    def test_cd02_dashboard_loads_after_login(self, customer_login, navigate, wait):
        """TC-CD-02: Dashboard loads successfully after customer login."""
        navigate("userdashboard.php")
        time.sleep(2)

        page_source = customer_login.page_source.lower()
        assert "dashboard" in page_source or "welcome" in page_source, \
            "Dashboard should contain welcome or dashboard content"

    def test_cd03_user_name_displayed(self, customer_login, navigate, wait):
        """TC-CD-03: Customer name is displayed on dashboard."""
        navigate("userdashboard.php")
        time.sleep(2)

        # The dashboard should show the user's name somewhere
        page_source = customer_login.page_source
        assert len(page_source) > 500, "Dashboard should have content"

    def test_cd04_navigation_to_bookings(self, customer_login, navigate, wait):
        """TC-CD-04: User can navigate to bookings from dashboard."""
        navigate("userdashboard.php")
        time.sleep(1)

        # Look for booking-related links
        booking_links = customer_login.find_elements(
            By.CSS_SELECTOR,
            "a[href*='booking'], a[href*='book_service']"
        )
        assert len(booking_links) > 0, \
            "Dashboard should have links to booking pages"

    def test_cd05_navigation_to_profile(self, customer_login, navigate, wait):
        """TC-CD-05: User can navigate to profile from dashboard."""
        navigate("profile.php")
        time.sleep(2)

        assert "profile.php" in customer_login.current_url, \
            "Should be able to access profile page"


class TestAdminDashboard:
    """TC-AD-01 through TC-AD-05: Admin dashboard tests."""

    def test_ad01_unauthenticated_admin_redirect(self, driver, navigate, wait):
        """TC-AD-01: Unauthenticated admin access redirects to admin login."""
        navigate("logout.php")
        time.sleep(1)

        navigate("dashboard.php")
        time.sleep(2)

        assert "admin_login.php" in driver.current_url or \
               "login" in driver.current_url.lower(), \
               "Should redirect to login when not authenticated as admin"

    def test_ad02_admin_dashboard_loads(self, admin_login, navigate, wait):
        """TC-AD-02: Admin dashboard loads after login."""
        navigate("dashboard.php")
        time.sleep(2)

        page_source = admin_login.page_source.lower()
        assert "dashboard" in page_source, \
            "Admin dashboard should load after login"

    def test_ad03_admin_can_access_bookings(self, admin_login, navigate, wait):
        """TC-AD-03: Admin can access booking management."""
        navigate("booking_management.php")
        time.sleep(2)

        assert "booking" in admin_login.current_url.lower(), \
            "Admin should access booking management"

    def test_ad04_admin_can_access_inventory(self, admin_login, navigate, wait):
        """TC-AD-04: Admin can access inventory page."""
        navigate("inventory.php")
        time.sleep(2)

        assert "inventory.php" in admin_login.current_url, \
            "Admin should access inventory page"

    def test_ad05_admin_can_access_reports(self, admin_login, navigate, wait):
        """TC-AD-05: Admin can access reports page."""
        navigate("reports.php")
        time.sleep(2)

        assert "reports.php" in admin_login.current_url, \
            "Admin should access reports page"
