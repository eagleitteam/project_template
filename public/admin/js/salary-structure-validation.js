$("#personal_btn").on("click", function () {
    // Remove the "was-validated" class from the form
    $("form").removeClass("was-validated");

    // Validate the personal information form
    if (validateBasicInfo()) {
        // Remove active classes from "Personal Info" tab and content
        $("#gen-info, #gen-info-tab").removeClass("show active");

        // Add active classes to "Leave Info" tab and content
        $("#allowance, #allowance-tab").addClass("show active");
    } else {
        // Scroll to the first element with an error
        scrollToFirstError();
    }
});

function validateBasicInfo() {
    let isValid = true;

    // Employee ID validation
    isValid =
        validateField(
            "#Emp_Code",
            ".Emp_Code_err",
            "The Employee ID field is required"
        ) && isValid;

    // Employee Name validation
    isValid =
        validateField("#emp_name", ".emp_name_err", "The Employee Name field is required") &&
        isValid;

    // Pay Scale validation
    isValid =
        validateField("#pay_scale_id", ".pay_scale_id_err", "The Pay Scale field is required") &&
        isValid;

    // Basic Salary validation
    isValid =
        validateField("#basic_salary", ".basic_salary_err", "The Basic Salary field is required") &&
        isValid;

    // Grade Pay validation
    isValid =
        validateField("#grade_pay", ".grade_pay_err", "The Grade Pay field is required") &&
        isValid;

    return isValid;
}

function validateField(inputId, errorClass, errorMessage) {
    let input = $(inputId);
    let error = $(errorClass);

    if (input.val().trim() === "") {
        error.text(errorMessage);
        input.removeClass('is-valid');
        return false;
    } else {
        error.text("");
        input.addClass('is-valid');
        return true;
    }
}

function scrollToFirstError() {
    // Find the first element with an error and scroll to it
    let firstErrorElement = $(".text-danger:visible").first();

    if (firstErrorElement.length) {
        firstErrorElement[0].scrollIntoView({
            behavior: "smooth",
            block: "start",
            inline: "start",
        });
    }
}

function findTabIdForField(fieldName) {

    switch (fieldName) {
        // Basic tab fields
        case 'employee_id':
        case 'Emp_Code':
        case 'pay_scale_id':
        case 'basic_salary':
        case 'grade_pay':

            return 'gen-info';

        // Allowance Details tab fields
        case 'allowance_amt':
        case 'allowance_type':
        case 'allowance_is_active':

            return 'allowance';

        // Deduction Details tab fields
        case 'deduction_amt':
        case 'deduction_type':
        case 'deduction_is_active':

            return 'deduction';

        default:
            return 'gen-info'; // Default to Personal tab if not found
    }
}
