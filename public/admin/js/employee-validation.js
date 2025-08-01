$(document).ready(function () {
    // Add event listener to the checkbox
    $("#is_applicable").change(function () {
        if ($(this).prop("checked")) {
            // If checked, copy present address to permanent address and set fields to readonly
            copyAddress();
            setPermanentAddressReadonly(true);
        } else {
            // If unchecked, clear permanent address and remove readonly
            clearPermanentAddress();
            setPermanentAddressReadonly(false);
        }
    });

    // Add event listener to present address fields
    $("#ccity, #caddress, #cstate, #cpincode").on("input", function () {
        if ($("#is_applicable").prop("checked")) {
            // If checkbox is checked, update permanent address when present address changes
            copyAddress();
        }
    });

    $("#personal_btn").on("click", function () {
        // Remove the "was-validated" class from the form
        $("form").removeClass("was-validated");

        // Validate the personal information form
        if (validatePersonalInfo()) {
            // Remove active classes from "Personal Info" tab and content
            $("#gen-info, #gen-info-tab").removeClass("show active");

            // Add active classes to "Leave Info" tab and content
            $("#leave-info, #leave-info-tab").addClass("show active");
        } else {
            // Scroll to the first element with an error
            scrollToFirstError();
        }
    });

    function validatePersonalInfo() {
        let isValid = true;

        // Employee ID validation
        isValid =
            validateField(
                "#employee_id",
                ".employee_id_err",
                "The Employee ID field is required"
            ) && isValid;

        // First Name validation
        isValid =
            validateField("#fname", ".fname_err", "The First Name field is required") &&
            isValid;

        // Middle Name validation (optional)
        // No validation for middle name, as it's optional

        // Last Name validation
        isValid =
            validateField("#lname", ".lname_err", "The Last Name field is required") &&
            isValid;

        // Gender validation
        isValid =
            validateField("#gender", ".gender_err", "The Gender field is required") &&
            isValid;

        // Date of Birth validation
        isValid =
            validateField("#dob", ".dob_err", "The Date of Birth field is required") &&
            isValid;

        // Date of Joining validation
        isValid =
            validateField("#doj", ".doj_err", "The Date of Joining field is required") &&
            isValid;

        // Mobile Number validation
        // isValid = validateMobile("#mobile_number", ".mobile_number_err") && isValid;
        // isValid =
        //     validateField(
        //         "#mobile_number",
        //         ".mobile_number_err",
        //         "The Mobile Number field is required"
        //     ) && isValid;

        // Email validation (optional)
        // No validation for email, as it's optional
        // Aadhar Card Number validation
        // isValid = validateAadhar("#aadhar", ".aadhar_err") && isValid;

        // Pan Card Number validation
        // isValid = validatePan("#pan", ".pan_err") && isValid;

        // PF account no validation
        isValid =
        validateField(
            "#pf_account_no",
            ".pf_account_no_err",
            "The PF Account No. field is required"
        ) && isValid;


        // Caste validation
        // isValid =
        //     validateField("#caste", ".caste_err", "The Caste field is required") &&
        //     isValid;

        // Blood Group validation
        // isValid =
        //     validateField(
        //         "#blood_group",
        //         ".blood_group_err",
        //         "The Blood Group field is required"
        //     ) && isValid;

        // Present Address validation
        // isValid =
        //     validateField("#ccity", ".ccity_err", "The City field is required") &&
        //     isValid;
        // isValid =
        //     validateField(
        //         "#caddress",
        //         ".caddress_err",
        //         "The Present Address field is required"
        //     ) && isValid;
        // isValid =
        //     validateField("#cstate", ".cstate_err", "The State field is required") &&
        //     isValid;
        // isValid =
        //     validateField(
        //         "#cpincode",
        //         ".cpincode_err",
        //         "The Pincode field is required"
        //     ) && isValid;

        // // Permanent Address validation
        // isValid =
        //     validateField("#pcity", ".pcity_err", "The City field is required") &&
        //     isValid;
        // isValid =
        //     validateField(
        //         "#paddress",
        //         ".paddress_err",
        //         "The Present Address field is required"
        //     ) && isValid;
        // isValid =
        //     validateField("#pstate", ".pstate_err", "The State field is required") &&
        //     isValid;
        // isValid =
        //     validateField(
        //         "#ppincode",
        //         ".ppincode_err",
        //         "The Pincode field is required"
        //     ) && isValid;

        // Work Details validation
        isValid =
            validateField("#ward_id", ".ward_id_err", "The Ward field is required") &&
            isValid;
        isValid =
            validateField(
                "#department_id",
                ".department_id_err",
                "The Department field is required"
            ) && isValid;
        isValid =
            validateField("#clas_id", ".clas_id_err", "The Class field is required") &&
            isValid;
        isValid =
            validateField(
                "#designation_id",
                ".designation_id_err",
                "The Designation field is required"
            ) && isValid;
        isValid =
            validateField("#shift", ".shift_err", "The Shift field is required") &&
            isValid;
        isValid =
            validateField(
                "#working_type",
                ".working_type_err",
                "The Working Type field is required"
            ) && isValid;
        isValid =
            validateField(
                "#retirement_date",
                ".retirement_date_err",
                "The Date of Retirement field is required"
            ) && isValid;
        isValid =
            validateField(
                "#increment_month",
                ".increment_month_err",
                "The Increment Month field is required"
            ) && isValid;
        isValid =
            validateField(
                "#employee_category",
                ".employee_category_err",
                "The Employee Category field is required"
        ) && isValid;

        return isValid;
    }

    function validateMobile(inputId, errorClass) {
        let mobileInput = $(inputId); // Convert to jQuery object
        let mobileError = $(errorClass);

        let mobileNumber = mobileInput.val().toString(); // Convert input value to string
        if (mobileNumber.length !== 10) {
            mobileError.text("Mobile number must be exactly 10 digits");
            return false;
        } else {
            mobileError.text("");
            return true;
        }
    }


    function validateAadhar(inputId, errorClass) {
        let aadharInput = $(inputId);
        let aadharError = $(errorClass);

        // Simple Aadhar pattern: 12 digits
        let aadharPattern = /^\d{12}$/;
        let aadharPatternNumber = aadharInput.val().toString(); // Convert input value to string

        if (aadharPatternNumber.length !== 12) {
            aadharError.text("Aadhar number must be exactly 12 digits");
            return false;
        }
        else if (!aadharPattern.test(aadharInput.val().trim())) {
            aadharError.text("The Aadhar field is required in respective manner");
            return false;
        } else {
            aadharError.text("");
            aadharInput.addClass('is-valid');
            return true;
        }
    }

    function validatePan(inputId, errorClass) {
        let panInput = $(inputId);
        let panError = $(errorClass);

        // Capitalize Pan Card input
        let capitalizedPan = panInput.val().trim().toUpperCase();

        // Simple Pan pattern: 5 uppercase letters, 4 digits, 1 uppercase letter
        let panPattern = /^[A-Z]{5}\d{4}[A-Z]$/;

        if (!panPattern.test(capitalizedPan)) {
            panError.text("The PAN Card field is required");
            return false;
        } else {
            // Update the input value to the capitalized version
            panInput.val(capitalizedPan);
            panError.text("");
            panInput.addClass('is-valid');
            return true;
        }
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

    function copyAddress() {
        // Copy present address values to permanent address
        $("#pcity").val($("#ccity").val());
        $("#paddress").val($("#caddress").val());
        $("#pstate").val($("#cstate").val());
        $("#ppincode").val($("#cpincode").val());
    }

    function clearPermanentAddress() {
        // Clear permanent address values
        $("#pcity").val("");
        $("#paddress").val("");
        $("#pstate").val("");
        $("#ppincode").val("");
    }

    function setPermanentAddressReadonly(readonly) {
        // Set readonly attribute for permanent address fields
        $("#pcity").prop("readonly", readonly);
        $("#paddress").prop("readonly", readonly);
        $("#pstate").prop("readonly", readonly);
        $("#ppincode").prop("readonly", readonly);
    }
});

// For Qualification dynamically row added start 2024/02/05
function addEducationRow() {
    var educationBody = document.getElementById("educationBody");
    var newRow = educationBody.insertRow(educationBody.rows.length);
    var cols = 7; // Number of columns in your table

    for (var i = 0; i < cols; i++) {
        var cell = newRow.insertCell(i);
        var input = document.createElement("input");

        if (i === cols - 1) {
            var button = document.createElement("button");
            button.type = "button";
            button.className = "btn btn-danger";
            button.innerHTML = "Remove";
            button.onclick = function () {
                removeRow(this);
            };
            cell.appendChild(button);
        } else {
            input.type =
                i === cols - 2 ? "file" : i === 0 || i === 1 ? "date" : "text";
            input.className = "form-control";
            input.name =
                i === 0
                    ? "qfrom[]"
                    : i === 1
                    ? "qto[]"
                    : i === 2
                    ? "qcertificate[]"
                    : i === 3
                    ? "qboard[]"
                    : i === 4
                    ? "qmarks[]"
                    : i === 5
                    ? "qdocument[]"
                    : "Document";

            input.placeholder =
                            i === 0
                            ? "From MM/YY"
                            : i === 1
                            ? "To MM/YY"
                            : i === 2
                            ? "Certificate Gained"
                            : i === 3
                            ? "Board / University"
                            : i === 4
                            ? "Marks / Grade"
                            : i === 5
                            ? "Document"
                            : "Document";
            cell.appendChild(input);
        }
    }
}

function addExperienceRow() {
    var experienceBody = document.getElementById("experienceBody");
    var newRow = experienceBody.insertRow(experienceBody.rows.length);
    var cols = 5; // Number of columns in your table

    for (var i = 0; i < cols; i++) {
        var cell = newRow.insertCell(i);
        var input = document.createElement("input");

        if (i === cols - 1) {
            var button = document.createElement("button");
            button.type = "button";
            button.className = "btn btn-danger";
            button.innerHTML = "Remove";
            button.onclick = function () {
                removeRow(this);
            };
            cell.appendChild(button);
        } else {
            input.type =
                i === cols - 2 ? "text" : i === 0 || i === 1 ? "date" : "text";
            input.className = "form-control";
            input.name =
                i === 0
                    ? "efrom[]"
                    : i === 1
                    ? "eto[]"
                    : i === 2
                    ? "ename_address[]"
                    : i === 3
                    ? "edesignation[]"
                    : "Document";

            input.placeholder =
                    i === 0
                    ? "From MM/YY"
                    : i === 1
                    ? "To MM/YY"
                    : i === 2
                    ? "Name & Address of Organisation"
                    : i === 3
                    ? "Designation"
                    : "Document";

            cell.appendChild(input);
        }
    }
}


function removeRow(button) {
    var tableBody = button.parentNode.parentNode.parentNode;
    var row = button.parentNode.parentNode;

    var isExistingRecord = row.querySelector('input[name="experienceId[]"]') !== null;

    var isExistingacademics = row.querySelector('input[name="academicDetailsId[]"]') !== null;

    var isExistingimpDocuments = row.querySelector('input[name="importantDocumentId[]"]') !== null;
    // Ensure at least one row is always visible
    if (tableBody.rows.length > 1) {
        row.parentNode.removeChild(row);
    }

    if (isExistingRecord) {

        var deleteInput = document.createElement("input");
        deleteInput.type = "hidden";
        deleteInput.name = "deletedExperienceIds[]";
        deleteInput.value = row.querySelector('input[name="experienceId[]"]').value;
        tableBody.appendChild(deleteInput);
    }

    if (isExistingacademics) {

        var deleteInput = document.createElement("input");
        deleteInput.type = "hidden";
        deleteInput.name = "deletedAcademicIds[]";
        deleteInput.value = row.querySelector('input[name="academicDetailsId[]"]').value;
        tableBody.appendChild(deleteInput);
    }

    if (isExistingimpDocuments) {

        var deleteInput = document.createElement("input");
        deleteInput.type = "hidden";
        deleteInput.name = "deletedImpDocIds[]";
        deleteInput.value = row.querySelector('input[name="importantDocumentId[]"]').value;
        tableBody.appendChild(deleteInput);
    }
}
// For Qualification dynamically row added End
