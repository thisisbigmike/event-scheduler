// Front-end behaviour (jQuery).
$(function () {

    // ---- Live venue clash check on the event form ----
    var $form = $('#event-form');
    if ($form.length) {
        function checkClash() {
            var data = {
                venue_id:   $('#venue_id').val(),
                event_date: $('#event_date').val(),
                start_time: $('#start_time').val(),
                end_time:   $('#end_time').val(),
                event_id:   $form.data('event-id')
            };
            var $box = $('#clash-status');
            if ($('#status').val() === 'Cancelled') { $box.empty(); return; }

            $.getJSON('check_clash.php', data, function (res) {
                if (!res.ready) { $box.empty(); return; }
                $box.attr('class', 'span-2 clash ' + (res.ok ? 'clash-ok' : 'clash-bad')).text(res.message);
            });
        }
        $('#venue_id, #event_date, #start_time, #end_time, #status').on('change', checkClash);
        checkClash();

        // Select / unselect all attendees
        $('#select-all').on('change', function () {
            $('.attendee').prop('checked', this.checked);
        });
    }

    // ---- Ask before destructive actions ----
    $('.confirm').on('click', function (e) {
        if (!confirm('Are you sure?')) e.preventDefault();
    });

    // ---- Preview passport photo before upload ----
    $('#photo').on('change', function () {
        var file = this.files[0];
        if (file) {
            $('#photo-preview').attr('src', URL.createObjectURL(file)).prop('hidden', false);
        }
    });
});
