callService=function(){
    $.ajax({
        method: 'POST',
        url: 'serv.php',
        data: {
                service: 'auth',
                rout: 'login',
                uname: $('#userid').val(),
                passwd: $('#passwd').val() }

    })
        .done(function (msg) {
            res=JSON.parse(msg);
            //alert(res.callpage);
            if(res.login==true)
            {
                $('#working-area').load(res.callpage);
            }
            $('#disp-msg').html(res.msg);
            $('#menu-area').load('serv.php', { 'component': 'navbar' });
        });
}
