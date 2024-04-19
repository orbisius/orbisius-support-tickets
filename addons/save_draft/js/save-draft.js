jQuery(document).ready(function ($) {
    function saveStorage() {
        let data = {
            subject: $('#orbisius_support_tickets_data_subject').val(),
            message: $('#orbisius_support_tickets_data_message').val(),
            timestamp: new Date().getTime()
        };
        localStorage.setItem('ticketData', JSON.stringify(data));
    }

    function loadStorage() {
        let savedData = localStorage.getItem('ticketData');
        if (savedData) {
            savedData = JSON.parse(savedData);
            let currentTime = new Date().getTime();
            let timeDiff = (currentTime - savedData.timestamp) / 1000;

            // saved data is less than 7 days old (just an example, we might want to set it to clear after 7 days)
            if (timeDiff < 604800) {
                $('#orbisius_support_tickets_data_subject').val(savedData.subject);
                $('#orbisius_support_tickets_data_message').val(savedData.message);
            } else {
                localStorage.removeItem('ticketData');
            }
        }
    }

    setInterval(saveStorage, 5000);

    $('#orbisius_support_tickets_submit_ticket_form').submit(function () {
        localStorage.removeItem('ticketData');
    });

    loadStorage();
});
