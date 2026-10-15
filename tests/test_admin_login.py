"""
test_admin_login.py
Test cases for the WashFlow Admin Login Module (admin_login.php)

Modules tested:
  - Admin login page rendering
  - Valid admin login → dashboard redirect
  - Invalid admin credentials
  - Empty field validation
  - Password toggle
  - CSRF token presence
"""

import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from conftest import TEST_ADMIN_USERNAME, TEST_ADMIN_PASSWORD


class TestAdminLoginPage:
    """TC-AL-01 through TC-AL-03: Page rendering."""

    def test_al01_admin_login_page_loads(self, driver, navigate, wait):
        """TC-AL-01: Admin login page loads with all elements."""
        navigate("admin_login.php")

        form = wait.until(EC.presence_of_element_located((By.ID, "wfAdminForm")))
        assert form.is_displayed(), "Admin login form should be visible"

        username = driver.find_element(By.ID, "wfUsername")
        password = driver.find_element(By.ID, "wfPassword")
        login_btn = driver.find_element(By.ID, "wfLoginBtn")

        assert username.is_displayed(), "Username field should be visible"
        assert password.is_displayed(), "Password field should be visible"
        assert login_btn.is_displayed(), "Login button should be visible"

    def test_al02_admin_badge_displayed(self, driver, navigate, wait):
        """TC-AL-02: Admin badge/label is shown on the page."""
        navigate("admin_login.php")

        badge = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, ".wf-admin-badge")
        ))
        assert "ADMIN" in badge.text.upper(), "Admin badge should display ADMIN text"

    def test_al03_csrf_token_present(self, driver, navigate, wait):
        """TC-AL-03: CSRF token hidden field is present in the form."""
        navigate("admin_login.php")

        csrf_inputs = driver.find_elements(By.CSS_SELECTOR, "input[name='csrf_token']")
        assert len(csrf_inputs) > 0, "CSRF token hidden input should be present"


class TestAdminLoginFunctionality:
    """TC-AL-04 through TC-AL-08: Login functionality."""

    def test_al04_valid_admin_login(self, driver, navigate, wait):
        """TC-AL-04: Valid admin credentials redirect to dashboard."""
        navigate("admin_login.php")

        username_field = wait.until(EC.presence_of_element_located((By.ID, "wfUsername")))
        username_field.clear()
        username_field.send_keys(TEST_ADMIN_USERNAME)

        password_field = driver.find_element(By.ID, "wfPassword")
        password_field.clear()
        password_field.send_keys(TEST_ADMIN_PASSWORD)

        driver.find_element(By.ID, "wfLoginBtn").click()
        time.sleep(3)

        assert "dashboard.php" in driver.current_url, \
            f"Should redirect to admin dashboard, got: {driver.current_url}"

    def test_al05_invalid_admin_username(self, driver, navigate, wait):
        """TC-AL-05: Invalid username shows error."""
        navigate("admin_login.php")

        username_field = wait.until(EC.presence_of_element_located((By.ID, "wfUsername")))
        username_field.clear()
        username_field.send_keys("fakeadmin_notreal")

        password_field = driver.find_element(By.ID, "wfPassword")
        password_field.clear()
        password_field.send_keys("wrongpass")

        driver.find_element(By.ID, "wfLoginBtn").click()
        time.sleep(2)

        assert "admin_login.php" in driver.current_url, "Should stay on admin login page"

        errors = driver.find_elements(By.CSS_SELECTOR, ".wf-alert-danger")
        assert len(errors) > 0, "Error message should be displayed"

    def test_al06_invalid_admin_password(self, driver, navigate, wait):
        """TC-AL-06: Valid username with wrong password shows error."""
        navigate("admin_login.php")

        username_field = wait.until(EC.presence_of_element_located((By.ID, "wfUsername")))
        username_field.clear()
        username_field.send_keys(TEST_ADMIN_USERNAME)

        password_field = driver.find_element(By.ID, "wfPassword")
        password_field.clear()
        password_field.send_keys("TotallyWrongPassword!!")

        driver.find_element(By.ID, "wfLoginBtn").click()
        time.sleep(2)

        assert "admin_login.php" in driver.current_url, "Should stay on admin login page"

    def test_al07_empty_fields_prevented(self, driver, navigate, wait):
        """TC-AL-07: Empty fields should not submit the form."""
        navigate("admin_login.php")

        wait.until(EC.presence_of_element_located((By.ID, "wfUsername")))

        driver.find_element(By.ID, "wfLoginBtn").click()
        time.sleep(1)

        assert "admin_login.php" in driver.current_url, \
            "Should stay on admin login with empty fields"

    def test_al08_password_toggle(self, driver, navigate, wait):
        """TC-AL-08: Password toggle works on admin login."""
        navigate("admin_login.php")

        password_field = wait.until(EC.presence_of_element_located((By.ID, "wfPassword")))
        password_field.send_keys("AdminPass123")

        assert password_field.get_attribute("type") == "password"

        toggle = driver.find_element(By.ID, "wfTogglePassword")
        toggle.click()
        time.sleep(0.5)

        assert password_field.get_attribute("type") == "text", \
            "Password should be visible after toggle"

    def test_al09_back_to_home_link(self, driver, navigate, wait):
        """TC-AL-09: 'Back to Home' navigates to index.php."""
        navigate("admin_login.php")

        back_btn = wait.until(EC.element_to_be_clickable(
            (By.CSS_SELECTOR, ".wf-btn-back")
        ))
        back_btn.click()
        time.sleep(2)

        assert "index.php" in driver.current_url or driver.current_url.endswith("/")
