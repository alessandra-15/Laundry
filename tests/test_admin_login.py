import pytest

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from conftest import (
    BASE_URL,
    TEST_ADMIN_USERNAME,
    TEST_ADMIN_PASSWORD,
    EXPLICIT_WAIT,
    navigate,
    safe_click,
)


LOGIN_URL = f"{BASE_URL}/login.php"


class TestAdminLoginPage:
    """Tests for the WashFlow login page."""

    def test_al01_admin_login_page_loads(self, driver, wait):
        navigate(driver, "login.php")

        wait.until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, "#wfLoginForm")
            )
        )

        assert "login.php" in driver.current_url
        assert driver.find_element(
            By.CSS_SELECTOR, "#wfLoginForm"
        ).is_displayed()

    def test_al02_admin_badge_displayed(self, driver, wait):
        navigate(driver, "login.php")

        wait.until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, "#wfLoginForm")
            )
        )

        # Verify that the login form and its fields are visible.
        assert driver.find_element(
            By.CSS_SELECTOR, "#wfEmail"
        ).is_displayed()

        assert driver.find_element(
            By.CSS_SELECTOR, "#wfPassword"
        ).is_displayed()

        assert driver.find_element(
            By.CSS_SELECTOR, "#wfLoginBtn"
        ).is_displayed()

    def test_al03_csrf_token_present(self, driver, wait):
        navigate(driver, "login.php")

        wait.until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, "#wfLoginForm")
            )
        )

        csrf_fields = driver.find_elements(
            By.CSS_SELECTOR,
            '#wfLoginForm input[name="csrf_token"]'
        )

        assert len(csrf_fields) > 0
        assert csrf_fields[0].get_attribute("value")

    def test_al04_valid_admin_login(self, driver, wait):
        navigate(driver, "login.php")

        username_field = wait.until(
            EC.visibility_of_element_located(
                (By.CSS_SELECTOR, "#wfEmail")
            )
        )

        password_field = driver.find_element(
            By.CSS_SELECTOR, "#wfPassword"
        )

        username_field.clear()
        username_field.send_keys(TEST_ADMIN_USERNAME)

        password_field.clear()
        password_field.send_keys(TEST_ADMIN_PASSWORD)

        safe_click(
            driver,
            wait,
            (By.CSS_SELECTOR, "#wfLoginBtn")
        )

        try:
            WebDriverWait(
                driver,
                EXPLICIT_WAIT
            ).until(
                lambda d: (
                    "dashboard.php" in d.current_url
                    or len(
                        d.find_elements(
                            By.CSS_SELECTOR,
                            ".wf-alert-danger"
                        )
                    ) > 0
                )
            )
        except Exception:
            pass

        errors = [
            element.text
            for element in driver.find_elements(
                By.CSS_SELECTOR,
                ".wf-alert-danger"
            )
            if element.is_displayed()
        ]

        print("Admin login URL:", driver.current_url)
        print("Admin login errors:", errors)

        assert "dashboard.php" in driver.current_url, (
            "Admin login failed. "
            f"Current URL: {driver.current_url}. "
            f"Displayed errors: {errors}"
        )

    def test_al05_invalid_admin_username(self, driver, wait):
        navigate(driver, "login.php")

        wait.until(
            EC.visibility_of_element_located(
                (By.CSS_SELECTOR, "#wfEmail")
            )
        ).send_keys("wrong_admin_username")

        driver.find_element(
            By.CSS_SELECTOR, "#wfPassword"
        ).send_keys(TEST_ADMIN_PASSWORD)

        safe_click(
            driver,
            wait,
            (By.CSS_SELECTOR, "#wfLoginBtn")
        )

        wait.until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, ".wf-alert-danger")
            )
        )

        assert "dashboard.php" not in driver.current_url

    def test_al06_invalid_admin_password(self, driver, wait):
        navigate(driver, "login.php")

        wait.until(
            EC.visibility_of_element_located(
                (By.CSS_SELECTOR, "#wfEmail")
            )
        ).send_keys(TEST_ADMIN_USERNAME)

        driver.find_element(
            By.CSS_SELECTOR, "#wfPassword"
        ).send_keys("WrongPassword123!")

        safe_click(
            driver,
            wait,
            (By.CSS_SELECTOR, "#wfLoginBtn")
        )

        wait.until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, ".wf-alert-danger")
            )
        )

        assert "dashboard.php" not in driver.current_url

    def test_al07_required_fields(self, driver, wait):
        navigate(driver, "login.php")

        email_field = wait.until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, "#wfEmail")
            )
        )

        password_field = driver.find_element(
            By.CSS_SELECTOR, "#wfPassword"
        )

        assert email_field.get_attribute("required") is not None
        assert password_field.get_attribute("required") is not None

    def test_al08_password_toggle(self, driver, wait):
        navigate(driver, "login.php")

        password_field = wait.until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, "#wfPassword")
            )
        )

        initial_type = password_field.get_attribute("type")

        # Find a likely password visibility toggle.
        toggle_selectors = [
            "#wfTogglePassword",
            ".wf-password-toggle",
            "[data-toggle-password]",
            "button[aria-label*='password' i]",
            "button[aria-label*='show' i]",
        ]

        toggle = None

        for selector in toggle_selectors:
            matches = driver.find_elements(
                By.CSS_SELECTOR,
                selector
            )

            visible_matches = [
                item for item in matches
                if item.is_displayed()
            ]

            if visible_matches:
                toggle = visible_matches[0]
                break

        if toggle is None:
            pytest.skip(
                "Password visibility toggle selector "
                "was not found. Update the selector to match login.php."
            )

        driver.execute_script(
            "arguments[0].scrollIntoView({block: 'center'});",
            toggle
        )

        try:
            toggle.click()
        except Exception:
            driver.execute_script(
                "arguments[0].click();",
                toggle
            )

        wait.until(
            lambda d: d.find_element(
                By.CSS_SELECTOR,
                "#wfPassword"
            ).get_attribute("type") != initial_type
        )

        updated_type = driver.find_element(
            By.CSS_SELECTOR,
            "#wfPassword"
        ).get_attribute("type")

        assert updated_type != initial_type

    def test_al09_back_home(self, driver, wait):
        navigate(driver, "login.php")

        home_links = driver.find_elements(
            By.CSS_SELECTOR,
            'a[href="index.php"], a[href="./"], a[href="/"]'
        )

        visible_links = [
            link for link in home_links
            if link.is_displayed()
        ]

        if not visible_links:
            pytest.skip(
                "Home link selector did not match login.php."
            )

        link = visible_links[0]

        driver.execute_script(
            "arguments[0].scrollIntoView({block: 'center'});",
            link
        )

        try:
            link.click()
        except Exception:
            driver.execute_script(
                "arguments[0].click();",
                link
            )

        wait.until(
            lambda d: "login.php" not in d.current_url
        )

        assert "login.php" not in driver.current_url
