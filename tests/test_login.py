"""
test_login.py
Test cases for the WashFlow Customer Login Module (login.php)

Modules tested:
  - Valid customer login
  - Invalid credentials handling
  - Empty field validation
  - Password toggle visibility
  - Remember me checkbox
  - Navigation links (register, back to home)
"""

import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from conftest import TEST_CUSTOMER_EMAIL, TEST_CUSTOMER_PASSWORD


class TestCustomerLoginValid:
    """TC-LG-01 through TC-LG-03: Valid login scenarios."""

    def test_lg01_login_page_loads(self, driver, navigate, wait):
        """TC-LG-01: Login page loads with all form elements."""
        navigate("login.php")

        form = wait.until(EC.presence_of_element_located((By.ID, "wfLoginForm")))
        assert form.is_displayed(), "Login form should be visible"

        email_input = driver.find_element(By.ID, "wfEmail")
        password_input = driver.find_element(By.ID, "wfPassword")
        login_btn = driver.find_element(By.ID, "wfLoginBtn")

        assert email_input.is_displayed(), "Email input should be visible"
        assert password_input.is_displayed(), "Password input should be visible"
        assert login_btn.is_displayed(), "Login button should be visible"

    def test_lg02_valid_login_redirects_to_dashboard(self, driver, navigate, wait):
        """TC-LG-02: Valid credentials redirect to user dashboard."""
        navigate("login.php")

        email_field = wait.until(EC.presence_of_element_located((By.ID, "wfEmail")))
        email_field.clear()
        email_field.send_keys(TEST_CUSTOMER_EMAIL)

        password_field = driver.find_element(By.ID, "wfPassword")
        password_field.clear()
        password_field.send_keys(TEST_CUSTOMER_PASSWORD)

        driver.find_element(By.ID, "wfLoginBtn").click()
        time.sleep(3)

        # After login, should redirect to userdashboard.php
        assert "userdashboard.php" in driver.current_url or \
               "dashboard" in driver.current_url.lower(), \
               f"Should redirect to dashboard, got: {driver.current_url}"

    def test_lg03_logout_then_login_again(self, driver, navigate, wait):
        """TC-LG-03: User can logout and login again successfully."""
        # First logout
        navigate("logout.php")
        time.sleep(2)

        # Then login
        navigate("login.php")
        email_field = wait.until(EC.presence_of_element_located((By.ID, "wfEmail")))
        email_field.clear()
        email_field.send_keys(TEST_CUSTOMER_EMAIL)

        password_field = driver.find_element(By.ID, "wfPassword")
        password_field.clear()
        password_field.send_keys(TEST_CUSTOMER_PASSWORD)

        driver.find_element(By.ID, "wfLoginBtn").click()
        time.sleep(3)

        assert "login.php" not in driver.current_url, \
            "Should not remain on login page after valid login"


class TestCustomerLoginInvalid:
    """TC-LG-04 through TC-LG-07: Invalid login scenarios."""

    def test_lg04_invalid_email_shows_error(self, driver, navigate, wait):
        """TC-LG-04: Wrong email shows error message."""
        navigate("login.php")

        email_field = wait.until(EC.presence_of_element_located((By.ID, "wfEmail")))
        email_field.clear()
        email_field.send_keys("wronguser@invalid.com")

        password_field = driver.find_element(By.ID, "wfPassword")
        password_field.clear()
        password_field.send_keys("WrongPassword123")

        driver.find_element(By.ID, "wfLoginBtn").click()
        time.sleep(2)

        # Should stay on login page with error
        assert "login.php" in driver.current_url, "Should stay on login page"

        error_alerts = driver.find_elements(By.CSS_SELECTOR, ".wf-alert-danger")
        assert len(error_alerts) > 0, "Error alert should be displayed"

    def test_lg05_invalid_password_shows_error(self, driver, navigate, wait):
        """TC-LG-05: Correct email but wrong password shows error."""
        navigate("login.php")

        email_field = wait.until(EC.presence_of_element_located((By.ID, "wfEmail")))
        email_field.clear()
        email_field.send_keys(TEST_CUSTOMER_EMAIL)

        password_field = driver.find_element(By.ID, "wfPassword")
        password_field.clear()
        password_field.send_keys("CompletelyWrong!999")

        driver.find_element(By.ID, "wfLoginBtn").click()
        time.sleep(2)

        assert "login.php" in driver.current_url, "Should stay on login page"

    def test_lg06_empty_email_prevents_submit(self, driver, navigate, wait):
        """TC-LG-06: Empty email field should prevent form submission."""
        navigate("login.php")

        # Only fill password
        password_field = wait.until(EC.presence_of_element_located((By.ID, "wfPassword")))
        password_field.clear()
        password_field.send_keys("SomePassword123")

        email_field = driver.find_element(By.ID, "wfEmail")
        email_field.clear()  # Ensure empty

        driver.find_element(By.ID, "wfLoginBtn").click()
        time.sleep(1)

        # Should stay on login page (client-side validation)
        assert "login.php" in driver.current_url, \
            "Should stay on login page with empty email"

    def test_lg07_empty_password_prevents_submit(self, driver, navigate, wait):
        """TC-LG-07: Empty password field should prevent form submission."""
        navigate("login.php")

        email_field = wait.until(EC.presence_of_element_located((By.ID, "wfEmail")))
        email_field.clear()
        email_field.send_keys(TEST_CUSTOMER_EMAIL)

        password_field = driver.find_element(By.ID, "wfPassword")
        password_field.clear()  # Empty password

        driver.find_element(By.ID, "wfLoginBtn").click()
        time.sleep(1)

        assert "login.php" in driver.current_url, \
            "Should stay on login page with empty password"


class TestLoginUIElements:
    """TC-LG-08 through TC-LG-11: UI interaction tests."""

    def test_lg08_password_toggle_visibility(self, driver, navigate, wait):
        """TC-LG-08: Password toggle switches between visible/hidden."""
        navigate("login.php")

        password_field = wait.until(EC.presence_of_element_located((By.ID, "wfPassword")))
        password_field.send_keys("TestPassword123")

        assert password_field.get_attribute("type") == "password", \
            "Password should be hidden by default"

        toggle = driver.find_element(By.ID, "wfTogglePassword")
        toggle.click()
        time.sleep(0.5)

        assert password_field.get_attribute("type") == "text", \
            "Password should be visible after toggle"

        toggle.click()
        time.sleep(0.5)

        assert password_field.get_attribute("type") == "password", \
            "Password should be hidden again after second toggle"

    def test_lg09_remember_me_checkbox(self, driver, navigate, wait):
        """TC-LG-09: Remember me checkbox is clickable."""
        navigate("login.php")

        checkbox = wait.until(EC.presence_of_element_located((By.ID, "wfRememberMe")))
        assert not checkbox.is_selected(), "Remember me should be unchecked by default"

        checkbox.click()
        assert checkbox.is_selected(), "Remember me should be checked after click"

    def test_lg10_register_link_navigates(self, driver, navigate, wait):
        """TC-LG-10: 'Create a new account' link goes to register.php."""
        navigate("login.php")

        register_link = wait.until(EC.element_to_be_clickable(
            (By.LINK_TEXT, "Create a new account")
        ))
        register_link.click()
        time.sleep(2)

        assert "register.php" in driver.current_url

    def test_lg11_back_to_home_link(self, driver, navigate, wait):
        """TC-LG-11: 'Back to Home' button navigates to index.php."""
        navigate("login.php")

        back_btn = wait.until(EC.element_to_be_clickable(
            (By.CSS_SELECTOR, ".wf-btn-back")
        ))
        back_btn.click()
        time.sleep(2)

        assert "index.php" in driver.current_url or driver.current_url.endswith("/")
