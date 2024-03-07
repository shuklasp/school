logout = function () {
    $.ajax({
        method: 'POST',
        url: 'serv.php',
        data: {
            service: 'auth',
            rout: 'logout',
        }

    })
        .done(function (resp) {
            res = JSON.parse(resp);
            $('#working-area').load(res.callpage);
            $('#disp-msg').html(res.msg);
            $('#menu-area').load('serv.php',{'component':'navbar'});

        });
}
