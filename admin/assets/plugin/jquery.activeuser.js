(function ($) {
  $.fn.activeUsers = function (options) {
    // Default settings, can be overridden by user
    let settings = $.extend(
      {
        url: "activeUser.php", // URL to fetch active user data
        interval: 30000,
      },
      options
    );
    let container = this;

    // Function to fetch active users via AJAX
    function fetchActiveUsers() {
      $.ajax({
        url: settings.url,
        method: "GET",
        dataType: "json",
        success: (response) => {
          const html = `
            <ul>
            ${
              Array.isArray(response)
                ? response
                    .map((user) => {
                      const name =
                        user.userName.charAt(0).toUpperCase() +
                        user.userName.slice(1);

                      const imagePath =
                        "../uploads/profile_images/" +
                        (user.profile_image || "default.jpg");

                      return `<li>
                  <img src="${imagePath}" alt="${name} Profile Picture
"
                  style="width:30px;height:30px;border-radius:50%;vertical-align:middle;margin-right:5px;">
                  ${name} (${user.user_type})
                </li>`;
                    })
                    .join("")
                : "<li>No active users found.</li>"
            }
            </ul>
            `;
          container.html(html);
        },
        error: (error) => {
          container.html(`
        <p style="color:red;">
          Failed to load active users. ${error.statusText || ""}
        </p>
      `);
          console.error("AJAX Error:", error);
        },
      });
    }

    fetchActiveUsers();
    setInterval(fetchActiveUsers, settings.interval);

    return this;
  };
})(jQuery);
