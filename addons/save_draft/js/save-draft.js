jQuery(document).ready(function ($) {
    // save the typed data to local storage

    // for tickets
    function saveTicketStorage() {
        let data = {
            subject: $('#orbisius_support_tickets_data_subject').val(),
            message: $('#orbisius_support_tickets_data_message').val(),
            timestamp: new Date().getTime()
        };
        localStorage.setItem('ticketData', JSON.stringify(data));
    }

    function loadTicketStorage() {
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

    setInterval(saveTicketStorage, 5000);

    $('#orbisius_support_tickets_submit_ticket_form').submit(function () {
        localStorage.removeItem('ticketData');
    });

    loadTicketStorage();

    // for comments
    function saveCommentStorage() {
        let data = {
            comment: $('#comment').val(),
            timestamp: new Date().getTime()
        };
        localStorage.setItem('commentData', JSON.stringify(data));
    }

    function loadCommentStorage() {
        let savedData = localStorage.getItem('commentData');
        if (savedData) {
            savedData = JSON.parse(savedData);
            let currentTime = new Date().getTime();
            let timeDiff = (currentTime - savedData.timestamp) / 1000;

            // saved data is less than 7 days old (just an example, we might want to set it to clear after 7 days)
            if (timeDiff < 604800) {
                $('#comment').val(savedData.comment);
            } else {
                localStorage.removeItem('commentData');
            }
        }
    }

    setInterval(saveCommentStorage, 5000);

    $('#commentform').submit(function () {
        localStorage.removeItem('commentData');
    });

    loadCommentStorage();
});
