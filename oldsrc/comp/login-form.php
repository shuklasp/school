<script lang="JavaScript">
    callService = function() {
        $.ajax({
                method: 'POST',
                url: 'serv.php',
                data: {
                    service: 'auth',
                    rout: 'login',
                    uname: $('#userid').val(),
                    passwd: $('#passwd').val()
                }

            })
            .done(function(msg) {
                res = JSON.parse(msg);
                //alert(res.callpage);
                if (res.login == true) {
                    $('#working-area').load(res.callpage);
                }
                $('#disp-msg').html(res.msg);
                $('#menu-area').load('serv.php', {
                    'component': 'navbar'
                });
            });
    }
</script>
<div class="row px-20" style="padding: 100px;">
    <h2>Login</h2>
</div>
<div id="login-form">
    <div class="form-group">
        <label for="userid">User ID</label>
        <input type="text" class="form-control mx-sm-10 w-75" size="30" id="userid" aria-describedby="userHelp">
        <small id="emailHelp" class="form-text text-muted">Enter your user id.</small>
    </div>
    <div class="form-group">
        <label for="passwd">Password</label>
        <input type="password" class="form-control w-75" id="passwd">
    </div>
    <button type="submit" class="btn btn-primary" onclick="callService()">Submit</button>
</div>