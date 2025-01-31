jQuery(document).ready(function ($) {
    // Activate the first tab and content by default
    $('.tab-content').first().addClass('active');
    $('.tab-item').first().addClass('active'); // Updated selector to match your HTML structure

    // On tab click
    $('.tab-item').click(function (e) {
        e.preventDefault();

        // Remove active classes from links and content
        $('.tab-item').removeClass('active');
        $('.tab-content').removeClass('active');

        // Add active class to clicked tab and show corresponding content
        $(this).addClass('active');
        let target = '#' + $(this).data('category-id'); // Assuming target ID corresponds to category ID
        $(target).addClass('active');
    });
    
});