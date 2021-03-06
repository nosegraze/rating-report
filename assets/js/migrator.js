(function ($) {

    var Rating_Report_Migrator = {

        init: function () {
            this.submit();
        },

        submit: function () {

            var self = this;

            $('#rating-report-migrate-button').click(function (e) {
                e.preventDefault();

                $(this).parent().empty().append('<span class="spinner is-active" style="float: none;"></span><div class="rating-report-migrator-progress"><div></div></div>');

                var deleteOldData = $('#rating-report-delete-old-data').val();

                // Start the import process.
                self.process_step(0, deleteOldData, self);
            });

        },

        process_step: function (step, deleteOldData, self) {

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'rating_report_migrate_data',
                    delete_old_data: deleteOldData,
                    step: step
                },
                dataType: "json",
                success: function (response) {
                    if ('done' == response.data.step) {
                        $('#rating-report-migration-area').empty().append('<p style="font-weight; bold; color: green;">' + response.data.message + '</p>');
                    } else {
                        $('.rating-report-migrator-progress div').animate({
                            width: response.data.percentage + '%'
                        }, 50, function () {
                            // Animation complete.
                        });

                        if (response.data.message != '') {
                            $('#rating-report-migrator-messages').append('<br>' + response.data.message);
                        }

                        self.process_step(parseInt(response.data.step), deleteOldData, self);
                    }

                }
            }).fail(function (response) {
                $('#rating-report-migration-area').empty().append(response.responseText);
                if (window.console && window.console.log) {
                    console.log(response);
                }
            })

        }

    };

    Rating_Report_Migrator.init();

})(jQuery);