# OpenACalendar Slack Incoming Webhooks

You will need the "Incoming WebHooks" integration in your Slack team. You'll also need a server with a cron, PHP and the PHP Curl extension. (On Ubuntu or Debian, simply install php-cli and php-curl.) If those requirements are meet, you won't need root access to install the actual script.

Create a ini config file, something like:

    site_url=http://opentechcalendar.co.uk/
    area_slug=62
    slack_incoming_webhook_url=https://hooks.slack.com/services/XXXXXXX/XXXXXX/XXXXXX 
    slack_channel="#general"
    slack_username="Open Tech Calendar"
    slack_icon_url="http://opentechcalendar.co.uk/theme/default/img/logo.png"

area_slug you can take from a webpage such as https://opentechcalendar.co.uk/area/62-edinburgh - so it's 62 for Edinburgh.

Then set up a cron entry:

    30 9 * * * php /path/to/eventsTodayAndTomorrow.php /path/to/your/config.ini

And that should be it!
