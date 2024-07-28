
sppview.events = {
    sppview: 'sppview'
};

var $obj = $('#sppview');

function getEventsList($obj) {
    var ev = new Array(),
        events = $obj.data('events'),
        i;
    for (i in events) { ev.push(i); }
    return ev.join(' ');
}

$obj.on(getEventsList($obj), function (e) {
    console.log(e);
});

$(document).ready(function () {
    $('#sppview').sppview();
    $(document).on(getEventsList($('#sppview')), function (e) {
        console.log(e);
    });
});