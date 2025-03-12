/*

Themes functions php
Soon im gonna make it plugin

*/

add_action('rest_api_init', 'register_events_rest_route');

function register_events_rest_route()
{
    register_rest_route(
        'custom/v2',
        '/events',
        array(
            'methods' => 'GET',
            'callback' => 'get_events',
        )
    );
}
function formatDateforcalendar($date, $dmtr = NULL)
{
    $months = array(
        'Ocak',
        'Şubat',
        'Mart',
        'Nisan',
        'Mayıs',
        'Haziran',
        'Temmuz',
        'Ağustos',
        'Eylül',
        'Ekim',
        'Kasım',
        'Aralık'
    );
    if ($date) {
        $date = explode('/', $date);

        if ($dmtr) {
            return $date[0] . ' ' . $months[$date[1] - 1];
        }
        $date = $date[2] . '-' . $date[1] . '-' . $date[0];
        return ($date);
    }
}
function get_events($request_data)
{

    $parameters = $request_data->get_params();
    $start_date = $parameters['start_date'];
    $start = $parameters['start'];
    $end = $parameters['end'];
    $events = array();
    $args = array(
        'post_type' => 'st_tours', //specifies you want to query the custom post type
        'event',
        'nopaging' => true,  // no pagination, but retrieve all testimonials at once
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $period = new \DatePeriod(
                new \DateTime(formatDateforcalendar(get_field("baslangic_tarihi"))),
                new \DateInterval('P1D'),
                new \DateTime(formatDateforcalendar(get_field("bitis_tarihi")))
            );
            if ($start_date) {
                if (get_field("baslangic_tarihi")) {


                    foreach ($period as $key => $value) {
                        if ($start_date == $value->format('Y-m-d')) {
                            $event_data = array(
                                'title' => html_entity_decode(get_the_title()),
                                'start' => formatDateforcalendar(get_field("baslangic_tarihi")),
                                'end' => formatDateforcalendar(get_field("bitis_tarihi")),
                                'start_end' => formatDateforcalendar(get_field("baslangic_tarihi"), true) . ' - ' . formatDateforcalendar(get_field("bitis_tarihi"), true),
                                'thumb' => get_the_post_thumbnail(get_the_ID(), 'thumbnail', array('loading' => 'lazy')),
                                'lnkhrf' => get_permalink()
                                // Add other fields as needed
                            );
                            $events[] = $event_data;
                        }

                    }
                }
            } else {

                if (get_field("baslangic_tarihi")) {
                    foreach ($period as $key => $value) {

                        $event_data = array(
                            'title' => html_entity_decode(get_the_title()),
                            'start' => $value->format('Y-m-d'),
                            'end' => $value->format('Y-m-d'),
                            'thumb' => get_the_post_thumbnail(get_the_ID(), 'thumbnail'),
                            'lnkhrf' => get_permalink(),
                            'sdate' => $start_date
                            // Add other fields as needed
                        );
                        $events[] = $event_data;
                    }
                }

            }
            wp_reset_postdata();
        }
        return rest_ensure_response($events);
    }
}
function cscripts()
{
    wp_register_script('tscalendarjs', 'https://uicdn.toast.com/calendar/latest/toastui-calendar.min.js', false);
    // This registers your script with a name so you can call it to enqueue it
    wp_enqueue_script('tscalendarjs');
    // enqueuing your script ensures it will be inserted in the propoer place in the header section
}
//            add_action('wp_enqueue_scripts', 'cscripts');
function ccss()
{
    wp_register_style('tscalendarcss', 'https://uicdn.toast.com/calendar/latest/toastui-calendar.min.css');
    wp_enqueue_style('tscalendarcss');
}
add_action('wp_enqueue_scripts', 'ccss');
function show_calendar_function($atts)
{
    ?>
    <div class="col-12">
        <div class="row display-flex">
            <div class="col-lg-6">
                <div class="calendar-wrp">
                    <div id="calendar" style=""></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div id="calendarView" style="">
                    <div id="calendarViewHead" style="display:none"></div>
                    <div id="calendarViewBody">

                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
}

// register your function here
add_shortcode('show_calendar', 'show_calendar_function');
function saveLog()
{
    ?>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <script>

        <?php
        function get_link_by_slug($slug, $lang_slug = null, $type = 'page')
        {
            $post = get_page_by_path($slug, OBJECT, $type);
            $id = ($lang_slug) ? pll_get_post($post->ID, $lang_slug) : $post->ID;
            return get_permalink($id);
        } ?>

        $('.list-destination .col-xs-12').ready(function () {
            $.each($('.list-destination .col-xs-12'), function (indexInArray, valueOfElement) {
                if (indexInArray == 4) {
                    $(valueOfElement).find('.st-link').on('click', function (e) {
                        e.preventDefault();
                        window.location.href = "<?= get_link_by_slug('tur-takvimi') ?>"

                    });
                    console.log()
                }
            });
        });

        function getdayevents(date) {
            $('#calendarViewBody').fadeOut();
            $('#calendarViewHead').fadeOut();

            $.ajax({
                type: "get",
                url: "<?= get_rest_url(null, 'custom/v2/events') ?>?start_date=" + date,
                data: { start_date: date },
                success: function (response) {
                    $('#calendarViewHead').html(date + ' Tarihli Etkinlikler');
                    $('#calendarViewHead').fadeIn();

                    $('#calendarViewBody').html(' ');
                    var divs = [];
                    if (response.length > 0) {
                        $.each(response, function (indexInArray, valueOfElement) {

                            $('#calendarViewBody').append('<a href="' + valueOfElement.lnkhrf + '" target="_blank"><div class="event">' + valueOfElement.thumb + '<span>' + valueOfElement.title + '</span><span>' + valueOfElement.start_end + '</span></div></a>');
                        });
                        console.log(divs)
                    } else {

                        $('#calendarViewBody').html('Etkinlik Bulunamadı');
                    }

                    $('#calendarViewBody').fadeIn();
                }
            });
        }
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'tr',
                themeSystem: 'bootstrap5',
                events: '<?= get_rest_url(null, 'custom/v2/events') ?>',
                eventClick: function (info) {
                    info.jsEvent.preventDefault(); // don't let the browser navigate
                    console.log(info);

                    if (info.event.url) {
                        window.open(info.event.url, '_blank');
                    }
                    info.el.style.borderColor = 'red';
                },
                dateClick: function (info) {
                    getdayevents(info.dateStr)

                },
                eventAfterAllRender: function (o) {
                    alert(2)
                },
                buttonText: {
                    today: 'Bugün',
                    month: 'Ay',
                    week: 'Hafta',
                    day: 'Gün',
                    list: 'Liste'
                }

            });
            calendar.render();
            $('body').on('click', '.fc-day', function () {
                $('.fc-day').removeClass('active');
                $(this).addClass('active');
            });

            $('body').on('click', '.a.fc-event', function (e) {
                e.preventDefault();
            });
            $('body').find('td[data-date="<?= date('Y-m-d') ?>"]').trigger("click", function () {
                alert('clicked');
            });
            $(function () {
                getdayevents('<?= date('Y-m-d') ?>');
            });

        });
    </script>
    <style>
        .list-destination .col-xs-12:nth-child(5) .desc a,
        .list-destination .col-xs-12:nth-child(5) .desc {
            display: none !important;
        }

        div#calendarViewBody {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-content: center;
            justify-content: flex-start;
            align-items: center;
            gap: 24px;
            width: 100%;
        }

        .calendar-wrp {
            position: sticky;
            top: 70px;
        }

        #calendarViewBody a {
            width: calc(50% - 12px);
        }

        div#calendarViewBody .event {
            position: relative;
            display: flex;
            flex-direction: column-reverse;
            flex-wrap: nowrap;
            align-content: flex-start;
            align-items: center;
            justify-content: space-between;
            border-radius: 36px;
            padding: 16px;
            overflow: hidden;
            min-height: 280px;
            -webkit-box-shadow: 0px 0px 71px -22px rgba(0, 0, 0, 0.75);
            -moz-box-shadow: 0px 0px 71px -22px rgba(0, 0, 0, 0.75);
            box-shadow: 0px 0px 71px -22px rgba(0, 0, 0, 0.75);
        }

        @keyframes img {
            0% {
                object-position: 00% 50%;
            }

            20% {
                object-position: 40% 50%;
            }

            40% {
                object-position: 50% 50%;
            }

            60% {
                object-position: 50% 50%;
            }

            80% {
                object-position: 100% 50%;
            }

            100% {
                object-position: 00% 50%;
            }
        }

        div#calendarViewBody .event img {
            animation-name: img;
            animation-duration: 3s;

            animation-fill-mode: both;
            animation-iteration-count: infinite;
            animation-timing-function: ease-in-out;
            flex-grow: 1;
            object-fit: cover;
            width: 100%;
            border-radius: 24px;
            position: absolute;
            height: 100%;
            top: 0;
            transition: all cubic-bezier(0.770, 0.000, 0.175, 1.000);
            /* easeInOutQuart */

            transition-timing-function: cubic-bezier(0.770, 0.000, 0.175, 1.000);
            /* easeInOutQuart */

            /* easeInOutQuart */
        }

        <?php for ($i = 1; $i < 101; $i++) {
            echo 'div#calendarViewBody .event img:nth-child(' . $i . ') {animation-delay: ' . ($i * 3) . 's}';
        } ?>
        div#calendarViewBody .event:hover img {
            object-position: 100% 50% !important;
        }

        div#calendarViewBody .event span:last-child {
            font-size: 14px;
            background: #5d2282;
            color: #fff;
            padding: 3px 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            z-index: 3;
        }

        div#calendarViewBody .event span:nth-child(2) {
            display: block;
            font-size: 16px;
            padding-top: 1px;
            text-align: center;
            z-index: 3;
            color: #fff;
            font-weight: 600;
        }

        div#calendarViewBody .event::before {
            content: "";
        }

        div#calendarViewBody .event:before {
            content: "";
            position: absolute;
            z-index: 2;
            background: rgb(93, 34, 130);
            background: -moz-linear-gradient(0deg, rgba(93, 34, 130, 0.6460959383753502) 0%, rgba(9, 9, 121, 0) 81%, rgba(0, 212, 255, 0) 100%);
            background: -webkit-linear-gradient(0deg, rgba(93, 34, 130, 0.6460959383753502) 0%, rgba(9, 9, 121, 0) 81%, rgba(0, 212, 255, 0) 100%);
            background: linear-gradient(0deg, rgba(93, 34, 130, 0.6460959383753502) 0%, rgba(9, 9, 121, 0) 81%, rgba(0, 212, 255, 0) 100%);
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#5d2282", endColorstr="#00d4ff", GradientType=1);
            width: 100%;
            height: 100%;
            top: 0;
        }

        div#calendarView {
            display: flex;
            flex-direction: column;
            align-items: center;
            align-content: center;
            gap: 24px;
        }

        div#calendarViewHead {
            font-size: 24px;
            position: relative;
            border: 1px solid;
            width: 100%;
            text-align: center;
            background: #dfd4e7;
            color: #5f2282;
            border-radius: 36px;
            padding: 12px;
            font-weight: bold;
            border: unset;
        }

        div#calendarViewHead:after {
            content: "";
            position: absolute;
            border-bottom: 1px solid;
        }

        .fc-theme-standard td,
        .fc-theme-standard th {
            border: unset !important;
        }

        .fc .fc-scrollgrid-liquid {
            border: unset !important;

        }

        .fc .fc-daygrid-day-number {
            color: #5d2282;
        }

        a.fc-col-header-cell-cushion {}

        .fc .fc-col-header-cell-cushion {
            color: #5f2282;
        }



        .fc-h-event .fc-event-title-container {
            z-index: 9999;
            width: 30px;
            display: block;
            height: 10px;
            border-radius: 100%;
            object-position: #5f2282;
        }

        .fc-h-event .fc-event-main-frame {
            border-radius: 100%;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-content: center;
            justify-content: center;
            background: transparent;
            align-items: center;
            width: 100%;
            margin: auto;
        }

        .fc-event-title.fc-sticky {
            display: none;
            border-radius: 100%;
        }

        .fc-direction-ltr .fc-daygrid-event.fc-event-end,
        .fc-direction-rtl .fc-daygrid-event.fc-event-start {
            background: #fff !important;
            border: 0px !important;
            outline: unset !important;
            border-radius: 100%;

            width: 10px;
        }

        .fc-daygrid-event-harness {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-content: center;
            justify-content: center;
            align-items: center;
        }

        .fc .fc-daygrid-body-unbalanced .fc-daygrid-day-events {
            display: flex;
            justify-content: center;
            flex-direction: row;
            flex-wrap: wrap;
            align-content: center;
            align-items: center;
            gap: 2px;
        }

        .fc .fc-daygrid-day-top {
            display: flex;
            justify-content: center;
            align-content: center;
            align-items: center;
            flex-direction: row;
        }

        .fc .fc-daygrid-day-number {
            width: 100%;
            text-align: center;
        }

        .fc.fc-media-screen.fc-direction-ltr.fc-theme-standard {
            background: rgb(93 34 130 / 20%);
            padding: 24px;
            border-radius: 36px;
        }

        .fc .fc-toolbar-title {
            color: #5f2282;
            padding-left: 25px;
        }

        .fc .fc-button-group>.fc-button {
            background: #5f2282 !important;
            border: 1px solid #5f2282 !important;
            outline: unset !important;
            box-shadow: unset !important;
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background: #5f2282;
            border-color: #5f2282 !important;
        }

        .fc .fc-button-primary {
            background: #5f2282 !important;
            border-color: #5f2282 !important;
        }

        td.fc-day.fc-day-mon.fc-day-future.fc-daygrid-day.active::before {
            content: "";
            width: calc(100% - 4px);
            height: calc(100% - 4px);
            background: #5e2281;
            position: absolute;
            opacity: 0.3;
            border-radius: 9px;
        }

        td.fc-day:hover:before {
            content: "";
            width: calc(100% - 4px);
            height: calc(100% - 4px);
            background: #5e2281;
            position: absolute;
            opacity: 0.3;
            border-radius: 9px;
        }

        td {
            position: relative !important;
        }

        td.fc-day {
            cursor: pointer;
        }

        .fc .fc-daygrid-day.fc-day-today {
            background: transparent;
        }

        .fc .fc-daygrid-day.fc-day-today::before {
            content: "";
            width: calc(100% - 4px);
            height: calc(100% - 4px);
            background: rgb(94 34 129 / 40%);
            position: absolute;
            opacity: 0.3;
            border-radius: 9px;
        }

        .row.display-flex {
            display: flex;
            flex-wrap: wrap;
        }

        .row.display-flex>[class*='col-'] {
            display: flex;
            flex-direction: column;
        }


        @media screen and (max-width: 992px) {

            .row.display-flex {
                padding: 0 12px;
            }

            div.col-lg-6 {
                width: 50%;
                margin-bottom: 24px;
            }

            #calendarViewBody a {
                width: 100%;
            }
        }

        @media screen and (max-width: 992px) and (orientation: portrait) {

            .row.display-flex {
                padding: 0 12px;
            }

            div.col-lg-6 {
                width: 100%;
                margin-bottom: 24px;
            }

        }
    </style>
    <?php
}

add_action('wp_footer', 'saveLog');
