/**
 * Main include file for SPP View module.
 */

spp.navigate=function(url){
	window.location.href=url;
}

spp.callService=function(url,data,callback){
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        dataType: "json",
        success: callback
    });
}

spp.alert=function(message){
    alert(message);
}

spp.confirm=function(message){
    return confirm(message);
}

spp.prompt=function(message){
    return prompt(message);
}

spp.updateSection=function(section){
    $('#section').val(section);
}