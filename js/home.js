loadmain=(page)=>{
    $("#working-area").load(page);
}

execMod=(url,mod)=>{
    //alert('This is'+mod);
    $.getScript(url, function(data, textStatus, jqxhr){
        //mod();
        window[mod]();
        //alert(data);
        //toast(mod);
        //mod='callService';
        //$('selector')[mod]();
    })
}