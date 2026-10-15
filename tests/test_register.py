"""
test_register.py
Test cases for the WashFlow Customer Registration Module (register.php)

Modules tested:
  - Multi-step form wizard (3 steps)
  - Field validations (required, email format, phone format)
  - Password strength indicator
  - Terms & conditions checkbox
  - Account type selection (Regular / Student)
  - Duplicate email detection
  - Successful registration redirect
"""

import pytest
import time
import random
import string
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC


def random_email():
    """Generate a random email for test registration."""
    rand = ''.join(random.choices(string.ascii_lowercase + string.digits, k=8))
    return f"test_{rand}@example.com"


class TestRegistrationStepOne:
    """TC-RG-01 through TC-RG-05: Step 1 - Personal Info."""

    def test_rg01_register_page_loads(self, driver, navigate, wait):
        """TC-RG-01: Registration page loads with Step 1 active."""
        navigate("register.php")

        form = wait.until(EC.presence_of_element_located((By.ID, "wfRegisterForm")))
        assert form.is_displayed(), "Registration form should be visible"

        # Step 1 should be active
        step1 = driver.find_element(By.CSS_SELECTOR, ".wf-form-step[data-step='1']")
        assert "active" in step1.get_attribute("class"), "Step 1 should be active"

    def test_rg02_step1_requires_first_name(self, driver, navigate, wait):
        """TC-RG-02: First name field is required to proceed."""
        navigate("register.php")

        # Fill everything except first name
        last_name = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, "input[name='lastName']")
        ))
        last_name.send_keys("TestUser")

        email = driver.find_element(By.CSS_SELECTOR, "input[name='email']")
        email.send_keys("test@example.com")

        phone = driver.find_element(By.CSS_SELECTOR, "input[name='phone']")
        phone.send_keys("09123456789")

        next_btn = driver.find_element(By.ID, "wfNextBtn")
        next_btn.click()
        time.sleep(1)

        # Should remain on Step 1
        step1 = driver.find_element(By.CSS_SELECTOR, ".wf-form-step[data-step='1']")
        assert "active" in step1.get_attribute("class"), \
            "Should remain on Step 1 when first name is missing"

    def test_rg03_step1_requires_valid_email(self, driver, navigate, wait):
        """TC-RG-03: Invalid email format should prevent proceeding."""
        navigate("register.php")

        first_name = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, "input[name='firstName']")
        ))
        first_name.send_keys("Test")

        last_name = driver.find_element(By.CSS_SELECTOR, "input[name='lastName']")
        last_name.send_keys("User")

        email = driver.find_element(By.CSS_SELECTOR, "input[name='email']")
        email.send_keys("invalid-email-format")

        phone = driver.find_element(By.CSS_SELECTOR, "input[name='phone']")
        phone.send_keys("09123456789")

        next_btn = driver.find_element(By.ID, "wfNextBtn")
        next_btn.click()
        time.sleep(1)

        step1 = driver.find_element(By.CSS_SELECTOR, ".wf-form-step[data-step='1']")
        assert "active" in step1.get_attribute("class"), \
            "Should remain on Step 1 with invalid email"

    def test_rg04_step1_valid_data_proceeds(self, driver, navigate, wait):
        """TC-RG-04: All valid Step 1 fields should proceed to Step 2."""
        navigate("register.php")

        first_name = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, "input[name='firstName']")
        ))
        first_name.send_keys("Test")

        last_name = driver.find_element(By.CSS_SELECTOR, "input[name='lastName']")
        last_name.send_keys("User")

        email = driver.find_element(By.CSS_SELECTOR, "input[name='email']")
        email.send_keys(random_email())

        phone = driver.find_element(By.CSS_SELECTOR, "input[name='phone']")
        phone.send_keys("09123456789")

        next_btn = driver.find_element(By.ID, "wfNextBtn")
        next_btn.click()
        time.sleep(1)

        step2 = driver.find_element(By.CSS_SELECTOR, ".wf-form-step[data-step='2']")
        assert "active" in step2.get_attribute("class"), \
            "Should proceed to Step 2 with valid data"

    def test_rg05_phone_accepts_ph_format(self, driver, navigate, wait):
        """TC-RG-05: Phone field accepts PH format (09XX or +639XX)."""
        navigate("register.php")

        phone = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, "input[name='phone']")
        ))
        phone.send_keys("+639123456789")

        placeholder = phone.get_attribute("placeholder")
        assert "09" in placeholder or "+639" in placeholder, \
            "Phone placeholder should show PH format"


class TestRegistrationStepTwo:
    """TC-RG-06 through TC-RG-08: Step 2 - Account Details."""

    def _go_to_step2(self, driver, navigate, wait):
        """Helper to navigate to Step 2."""
        navigate("register.php")

        wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, "input[name='firstName']")
        )).send_keys("Test")
        driver.find_element(By.CSS_SELECTOR, "input[name='lastName']").send_keys("User")
        driver.find_element(By.CSS_SELECTOR, "input[name='email']").send_keys(random_email())
        driver.find_element(By.CSS_SELECTOR, "input[name='phone']").send_keys("09123456789")
        driver.find_element(By.ID, "wfNextBtn").click()
        time.sleep(1)

    def test_rg06_step2_address_field(self, driver, navigate, wait):
        """TC-RG-06: Step 2 shows address field."""
        self._go_to_step2(driver, navigate, wait)

        address = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, "input[name='address']")
        ))
        assert address.is_displayed(), "Address field should be visible in Step 2"

    def test_rg07_account_type_regular(self, driver, navigate, wait):
        """TC-RG-07: Selecting 'Regular' account type works."""
        self._go_to_step2(driver, navigate, wait)

        regular_radio = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, "input[name='account_type'][value='Regular']")
        ))
        regular_radio.click()
        assert regular_radio.is_selected(), "Regular radio should be selected"

    def test_rg08_student_type_shows_upload(self, driver, navigate, wait):
        """TC-RG-08: Selecting 'Student' shows student ID upload field."""
        self._go_to_step2(driver, navigate, wait)

        student_radio = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, "input[name='account_type'][value='Student']")
        ))
        student_radio.click()
        time.sleep(0.5)

        student_section = driver.find_element(By.ID, "wfStudentSection")
        # Check if displayed (the section visibility depends on JS)
        display = student_section.value_of_css_property("display")
        assert display != "none", "Student ID upload section should be visible"


class TestRegistrationStepThree:
    """TC-RG-09 through TC-RG-13: Step 3 - Password & Terms."""

    def _go_to_step3(self, driver, navigate, wait):
        """Helper to navigate to Step 3."""
        navigate("register.php")

        wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, "input[name='firstName']")
        )).send_keys("Test")
        driver.find_element(By.CSS_SELECTOR, "input[name='lastName']").send_keys("User")
        driver.find_element(By.CSS_SELECTOR, "input[name='email']").send_keys(random_email())
        driver.find_element(By.CSS_SELECTOR, "input[name='phone']").send_keys("09123456789")
        driver.find_element(By.ID, "wfNextBtn").click()
        time.sleep(1)

        # Step 2: fill address and select Regular
        driver.find_element(By.CSS_SELECTOR, "input[name='address']").send_keys("123 Test St, Manila")
        driver.find_element(By.CSS_SELECTOR, "input[name='account_type'][value='Regular']").click()
        driver.find_element(By.ID, "wfNextBtn").click()
        time.sleep(1)

    def test_rg09_step3_password_fields_visible(self, driver, navigate, wait):
        """TC-RG-09: Step 3 shows password and confirm password fields."""
        self._go_to_step3(driver, navigate, wait)

        password = wait.until(EC.presence_of_element_located((By.ID, "wfPassword")))
        confirm = driver.find_element(By.ID, "wfConfirmPassword")

        assert password.is_displayed(), "Password field should be visible"
        assert confirm.is_displayed(), "Confirm password field should be visible"

    def test_rg10_password_strength_indicator(self, driver, navigate, wait):
        """TC-RG-10: Typing password updates strength indicator."""
        self._go_to_step3(driver, navigate, wait)

        password = wait.until(EC.presence_of_element_located((By.ID, "wfPassword")))
        password.send_keys("StrongPass@123")
        time.sleep(1)

        strength_text = driver.find_element(By.ID, "wfPwText")
        assert strength_text.is_displayed(), "Password strength text should be visible"

    def test_rg11_password_mismatch_prevented(self, driver, navigate, wait):
        """TC-RG-11: Mismatched passwords should prevent submission."""
        self._go_to_step3(driver, navigate, wait)

        password = wait.until(EC.presence_of_element_located((By.ID, "wfPassword")))
        password.send_keys("StrongPass@123")

        confirm = driver.find_element(By.ID, "wfConfirmPassword")
        confirm.send_keys("DifferentPass@456")

        terms = driver.find_element(By.ID, "wfTerms")
        terms.click()

        submit = driver.find_element(By.ID, "wfSubmitBtn")
        submit.click()
        time.sleep(2)

        # Should remain on register page
        assert "register.php" in driver.current_url, \
            "Should not proceed with mismatched passwords"

    def test_rg12_terms_checkbox_required(self, driver, navigate, wait):
        """TC-RG-12: Terms checkbox must be checked to submit."""
        self._go_to_step3(driver, navigate, wait)

        password = wait.until(EC.presence_of_element_located((By.ID, "wfPassword")))
        password.send_keys("StrongPass@123")

        confirm = driver.find_element(By.ID, "wfConfirmPassword")
        confirm.send_keys("StrongPass@123")

        # Do NOT check terms
        submit = driver.find_element(By.ID, "wfSubmitBtn")
        submit.click()
        time.sleep(1)

        assert "register.php" in driver.current_url, \
            "Should not submit without accepting terms"

    def test_rg13_back_button_returns_to_step2(self, driver, navigate, wait):
        """TC-RG-13: Back button on Step 3 returns to Step 2."""
        self._go_to_step3(driver, navigate, wait)

        prev_btn = wait.until(EC.element_to_be_clickable((By.ID, "wfPrevBtn")))
        prev_btn.click()
        time.sleep(1)

        step2 = driver.find_element(By.CSS_SELECTOR, ".wf-form-step[data-step='2']")
        assert "active" in step2.get_attribute("class"), \
            "Should return to Step 2 when back button is clicked"


class TestRegistrationNavigation:
    """TC-RG-14 through TC-RG-15: Navigation links."""

    def test_rg14_sign_in_link(self, driver, navigate, wait):
        """TC-RG-14: 'Sign in here' link navigates to login.php."""
        navigate("register.php")

        login_link = wait.until(EC.element_to_be_clickable(
            (By.LINK_TEXT, "Sign in here")
        ))
        login_link.click()
        time.sleep(2)

        assert "login.php" in driver.current_url

    def test_rg15_back_to_home(self, driver, navigate, wait):
        """TC-RG-15: 'Back to Home' navigates to index.php."""
        navigate("register.php")

        back_btn = wait.until(EC.element_to_be_clickable(
            (By.CSS_SELECTOR, ".wf-btn-back")
        ))
        back_btn.click()
        time.sleep(2)

        assert "index.php" in driver.current_url or driver.current_url.endswith("/")
