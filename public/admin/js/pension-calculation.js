function calculateValues() {
    var six_pay_basic = parseFloat($("#basic_salary").val());
    var Da_allowns = parseFloat($("#DA_rate").val());
    var emp_retire_after_2016 = parseInt($("#emp_retire_after_2016").val());

    $("#payable_pension").val('');

    var pay_type = parseInt($("#pay_type").val());

    if(pay_type == '')
    {
        pay_type = 2;
        $("#pay_type").val(2);
    }
    // For 6 Pay
    if(pay_type == 1){

        if(six_pay_basic != '' && six_pay_basic != 0)
        {
            var basic_amt = emp_retire_after_2016 == 2 ? six_pay_basic * 2.57 : six_pay_basic;

            if(basic_amt < 7500)
            {
                $('#calculated_basic').val(7500);
                var da = 7500 * Da_allowns / 100;
                $('#da').val(Math.round(da));
                $('#main_da').val(Math.round(da));
            }
            else
            {
                $('#calculated_basic').val(Math.round(basic_amt));
                var da = basic_amt * Da_allowns / 100;
                $('#da').val(Math.round(da));
                $('#main_da').val(Math.round(da));
            }

            // var is_emp_dr = $('#is_emp_dr').val();

            // if (is_emp_dr == 1)
            // {
            //     var new_basic = $('#calculated_basic').val();
            //     var business_allowances = new_basic * 35 / 100;
            //     $('#business_allowances').val(Math.round(business_allowances));
            // }
            // if (is_emp_dr == 2)
            // {
            //     $('#business_allowances').val("0");
            // }

        } else {
            alert("Please Enter Valid Amount");
        }

    } else {

        if(six_pay_basic != '' && six_pay_basic != 0)
        {
            var basic_amt = six_pay_basic;
            $('#calculated_basic').val(Math.round(basic_amt));
            var da = basic_amt * Da_allowns / 100;
            $('#da').val(Math.round(da));
            $('#main_da').val(Math.round(da));

            // var is_emp_dr = $('#is_emp_dr').val();

            // if (is_emp_dr == 1) {
            //     var new_basic = $('#calculated_basic').val();
            //     var business_allowances = new_basic * 35 / 100;
            //     $('#business_allowances').val(Math.round(business_allowances));
            // }
            // if (is_emp_dr == 2) {
            //     $('#business_allowances').val("0");
            // }

        } else {
            alert("Please Enter Valid Amount");
        }
    }
}

$(document).ready(function() {
    $("#basic_salary, #DA_rate, #emp_retire_after_2016").on("change keyup", calculateValues);
});


$("#dob").on("change", function() {
    var dob = new Date($(this).val());
    var today = new Date();
    var age = today.getFullYear() - dob.getFullYear();
    var m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    $("#age").val(age);
});

$("#sell_computation").on("change", function() {

    if (this.value == 1) {
        $('#deduct_amt').val(0);
        $('#deduct_amt').attr('required', 'required');

        $("#sell_date_div").removeAttr("style");
        $("#deduct_amt_div").removeAttr("style");

    }

    if (this.value == 2) {
        $('#deduct_amt').val(0);
        var main_basic = $('#calculated_basic').val();
        // var business_allowance = $('#business_allowances').val();
        // $('#payable_pension').val(Math.round(parseFloat(main_basic)) + parseFloat(business_allowance));
        $('#payable_pension').val(Math.round(parseFloat(main_basic)));

        $('#sell_date_div').hide();
        $("#deduct_amt_div").hide();
        $('#deduct_amt').removeAttr('required');
    }

});

$("#deduct_amt").keyup(function(){

    var deduct_amt = parseFloat($(this).val());
    var basic_amount = parseFloat($('#calculated_basic').val());
    // var business_allowance = $('#business_allowances').val();
    if(!isNaN(deduct_amt) && !isNaN(basic_amount))
    {
        // var new_basic = (parseFloat(basic_amount) + parseFloat(business_allowance)) - parseFloat(deduct_amt);
        var new_basic = (parseFloat(basic_amount)) - parseFloat(deduct_amt);
        $('#payable_pension').val(Math.round(new_basic));
    }

});

$("#da_applicabe").on("change", function() {
    if (this.value == 1) {
        var da = $('#main_da').val();
        $('#da').val(da);
    }
    if (this.value == 2) {
        $('#da').val(0);
    }
    })
