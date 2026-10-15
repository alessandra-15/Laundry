"""
conftest.py
Shared Appium fixtures for WashFlow Laundry System tests.
Uses Chrome browser on Android Studio emulator.

Prerequisites:
  1. Android Studio with an emulator running (e.g. Pixel 7 API 34)
  2. Appium server running: appium --relaxed-security
  3. ChromeDriver matching the emulator's Chrome version
  4. pip install Appium-Python-Client pytest

Usage:
  pytest tests/ -v
"""

import pytest
from appium import webdriver
from appium.options.android import UiAutomator2Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
import time

# ─── Configuration ───────────────────────────────────────────────
# Change BASE_URL to match your local server or deployed site
BASE_URL = "http://10.0.2.2:8000"  # 10.0.2.2 = host machine from Android emulator

APPIUM_SERVER = "http://127.0.0.1:4723"

# Test credentials — adjust to match your database
TEST_CUSTOMER_EMAIL = "testcustomer@example.com"
TEST_CUSTOMER_PASSWORD = "Test@1234"

TEST_ADMIN_USERNAME = "admin"
TEST_ADMIN_PASSWORD = "admin123"

IMPLICIT_WAIT = 10  # seconds
EXPLICIT_WAIT = 15  # seconds


@pytest.fixture(scope="session")
def driver():
    """
    Create and return an Appium driver for Chrome on Android emulator.
    This fixture is shared across all tests in the session.
    """
    options = UiAutomator2Options()
    options.platform_name = "Android"
    options.device_name = "emulator-5554"
    options.browser_name = "Chrome"
    options.no_reset = True
    options.auto_grant_permissions = True

    # If chromedriver isn't auto-detected, specify the path:
    # options.set_capability("chromedriverExecutable", "C:/path/to/chromedriver.exe")

    drv = webdriver.Remote(APPIUM_SERVER, options=options)
    drv.implicitly_wait(IMPLICIT_WAIT)

    yield drv

    drv.quit()


@pytest.fixture
def wait(driver):
    """Return a WebDriverWait instance for explicit waits."""
    return WebDriverWait(driver, EXPLICIT_WAIT)


@pytest.fixture
def navigate(driver):
    """Factory fixture to navigate to a page relative to BASE_URL."""
    def _go(path):
        url = f"{BASE_URL}/{path.lstrip('/')}"
        driver.get(url)
        time.sleep(1)  # Allow PHP page to fully render
    return _go


@pytest.fixture
def customer_login(driver, navigate, wait):
    """Log in as a test customer and return the driver."""
    navigate("login.php")

    email_field = wait.until(EC.presence_of_element_located((By.ID, "wfEmail")))
    email_field.clear()
    email_field.send_keys(TEST_CUSTOMER_EMAIL)

    password_field = driver.find_element(By.ID, "wfPassword")
    password_field.clear()
    password_field.send_keys(TEST_CUSTOMER_PASSWORD)

    driver.find_element(By.ID, "wfLoginBtn").click()
    time.sleep(2)

    return driver


@pytest.fixture
def admin_login(driver, navigate, wait):
    """Log in as admin and return the driver."""
    navigate("admin_login.php")

    username_field = wait.until(EC.presence_of_element_located((By.ID, "wfUsername")))
    username_field.clear()
    username_field.send_keys(TEST_ADMIN_USERNAME)

    password_field = driver.find_element(By.ID, "wfPassword")
    password_field.clear()
    password_field.send_keys(TEST_ADMIN_PASSWORD)

    driver.find_element(By.ID, "wfLoginBtn").click()
    time.sleep(2)

    return driver
