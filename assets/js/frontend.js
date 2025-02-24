jQuery($ => {
    if (!donation_settings.isLicensed) return;

    $('.donation-table-wrapper').each(function() {
        const $table = $(this).find('.donation-table tbody');
        const $rows = $table.find('tr');
        let index = 0;

        function slideUp() {
            $rows.removeClass('active');
            $($rows[index]).addClass('active');
            index = (index + 1) % $rows.length;
            setTimeout(slideUp, 2000);
        }

        $table.closest('.donation-table-wrapper').css({
            backgroundColor: donation_settings.bgColor,
            color: donation_settings.textColor,
            width: donation_settings.width,
            height: donation_settings.height
        });

        slideUp();
    });
});