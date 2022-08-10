function updateParameters() {

    if (type_obj == null)
        return;

    var typename = type_obj.value;

    //find the type
    var typeparams = findTheType(typename);

    if (typeparams != null) {

        typeparams_box_obj.innerHTML = renderParamBox(typeparams, typeparams_id, typeparams_obj.value);

        jQuery(function ($) {
            //container ||
            $(typeparams_box_obj).find(".hasPopover").popover({
                "html": true,
                "trigger": "hover focus",
                "container": "body"
            });
        });

        var param_att = typeparams["@attributes"];
        var rawquotes = false;
        if (typeof (param_att.rawquotes) != "undefined" && param_att.rawquotes == "1")
            rawquotes = true;

        var param_array = getParamOptions(typeparams.params, 'param');


        if (typeof (param_att.repeatative) !== "undefined" && param_att.repeatative === "1" && param_array.length == 1)
            updateParamString('fieldtype_param_', 1, -1, typeparams_id, null, rawquotes);//unlimited number of parameters
        else
            updateParamString('fieldtype_param_', 1, param_array.length, typeparams_id, null, rawquotes);

    } else
        typeparams_box_obj.innerHTML = '<p class="msg_error">Unknown Field Type</p>';
}

function renderInput_Radio(objname, param, value, onchange) {
    var param_att = param["@attributes"];

    var result = '<fieldset id="' + objname + '" class="btn-group btn-group-yesno radio">';//
    var options = param_att.options.split(",");

    for (var o = 0; o < options.length; o++) {
        var opt = options[o].split("|");
        var id = objname + "" + o;

        var c = 'btn';
        if (opt[0] == value) {
            result += '<input type="radio" id="' + id + '" name="' + objname + '" value="' + opt[0] + '" checked="checked" ' + onchange + ' />';

            c += ' active';
        } else {
            result += '<input type="radio" id="' + id + '" name="' + objname + '" value="' + opt[0] + '" ' + onchange + '  />';

        }

        if (opt[0] == value) {
            if (opt[0] != '' && opt[0] != '0')
                c += ' btn-success';
            else
                c += ' btn-danger';
        }

        result += '<label class="' + c + '" for="' + id + '" id="' + id + '_label" >' + opt[1] + '</label>';


    }

    result += '</fieldset>';

    return result;
}

