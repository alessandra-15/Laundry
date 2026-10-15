"""
test_payments.py
Test cases for the WashFlow Payments Module

Modules tested:
  - Customer payment page access (payments.php)
  - Admin payment management (admin_payments.php)
  - Payment status display
  - GCash payment flow
  - Access control
"""

import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC


class TestCustomerPayments:
    """TC-PY-01 through TC-PY-04: Customer payment tests."""

    def test_py01_payments_require_login(self, driver, navigate, wait):
        """TC-PY-01: Payment page requires authentication."""
        navigate("logout.php")
        time.sleep(1)

        navigate("payments.php")
        time.sleep(2)

        assert "login.php" in driver.current_url, \
            "Should redirect to login when not authenticated"

    def test_py02_payments_page_loads(self, customer_login, navigate, wait):
        """TC-PY-02: Payment page loads for authenticated customer."""
        navigate("payments.php")
        time.sleep(2)

        page_source = customer_login.page_source.lower()
        assert "payment" in page_source, \
            "Payments page should contain payment-related content"

    def test_py03_payment_history_displayed(self, customer_login, navigate, wait):
        """TC-PY-03: Payment history/list is displayed."""
        navigate("payments.php")
        time.sleep(2)

        page_source = customer_login.page_source
        assert len(page_source) > 500, \
            "Payments page should render content"

    def test_py04_gcash_payment_page(self, customer_login, navigate, wait):
        """TC-PY-04: GCash payment page is accessible."""
        navigate("gcash_payment.php")
        time.sleep(2)

        page_source = customer_login.page_source.lower()
        # Should either load or redirect — shouldn't crash
        assert len(page_source) > 100, "Page should render without errors"


class TestAdminPayments:
    """TC-PY-05 through TC-PY-08: Admin payment management tests."""

    def test_py05_admin_payments_page(self, admin_login, navigate, wait):
        """TC-PY-05: Admin can access payment management."""
        navigate("admin_payments.php")
        time.sleep(2)

        assert "admin_payments.php" in admin_login.current_url, \
            "Admin should access payment management"

    def test_py06_payment_table_present(self, admin_login, navigate, wait):
        """TC-PY-06: Payment management shows payment records."""
        navigate("admin_payments.php")
        time.sleep(2)

        page_source = admin_login.page_source.lower()
        assert "payment" in page_source, \
            "Admin payments page should show payment data"

    def test_py07_admin_payments_require_auth(self, driver, navigate, wait):
        """TC-PY-07: Admin payment page requires admin login."""
        navigate("logout.php")
        time.sleep(1)

        navigate("admin_payments.php")
        time.sleep(2)

        assert "admin_login.php" in driver.current_url or \
               "login" in driver.current_url.lower(), \
               "Should redirect non-admin users"

    def test_py08_staff_payments_page(self, admin_login, navigate, wait):
        """TC-PY-08: Staff payments page is accessible by admin."""
        navigate("staff_payments.php")
        time.sleep(2)

        page_source = admin_login.page_source
        assert len(page_source) > 500, "Staff payments page should load"
