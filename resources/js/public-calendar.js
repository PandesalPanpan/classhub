import { Calendar } from '@fullcalendar/core';
import resourcePlugin from '@fullcalendar/resource';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';
import resourceTimeGridDay from '@fullcalendar/resource-timegrid';

const palette = [
    '#2563eb', // blue-600
    '#7c3aed', // violet-600
    '#0891b2', // cyan-600
    '#16a34a', // green-600
    '#d97706', // amber-600
    '#dc2626', // red-600
    '#0ea5e9', // sky-500
    '#9333ea', // purple-600
];

function hashTitleToColor(title) {
    let hash = 0;
    for (let i = 0; i < title.length; i++) {
        hash = ((hash << 5) - hash + title.charCodeAt(i)) | 0; // djb2-ish
    }
    const idx = Math.abs(hash) % palette.length;
    return palette[idx];
}

function formatEventTitle(title) {
    if (!title) return '';
    // Only split at " - " after closing parenthesis (before professor name)
    return title.replace(/\)\s*-\s*/g, ')\n');
}

function formatEventTimeRange(start, end) {
    const fmt = (d) => {
        const h = d.getHours();
        const m = d.getMinutes();
        return `${h}:${m.toString().padStart(2, '0')}`;
    };
    return `${fmt(start)}-${fmt(end)}`;
}

function withHashedColors(evts) {
    return (evts || []).map((evt) => {
        const color = hashTitleToColor(evt.title || '');
        return { ...evt, backgroundColor: color, borderColor: color };
    });
}

// Expose for Livewire updates
window.withHashedColors = withHashedColors;

window.initClassroomCalendar = function (rooms, events) {
    const calendarEl = document.getElementById('classroom-calendar');

    if (!calendarEl || calendarEl.dataset.initialized) return;

    calendarEl.dataset.initialized = 'true';

    const calendar = new Calendar(calendarEl, {
        schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
        plugins: [resourcePlugin, resourceTimelinePlugin, interactionPlugin, resourceTimeGridDay],
        initialView: 'resourceTimeGridDay',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'resourceTimelineDay'
        },
        resources: rooms,
        events: withHashedColors(events),
        resourceAreaWidth: '10%',
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',
        slotLabelInterval: "00:30",
        height: 'auto',
        editable: false,
        selectable: false,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        nowIndicator: true,
        allDaySlot: false,
        expandRows: true,
        // displayEventTime: false,
        // contentHeight: 100,
        // eventMinHeight: 90,
        slotLabelClassNames: 'min-w-[100px]',
        // eventMinWidth: 100,
        // resourceLabelContent: function(arg) {
        //     return arg.resource.title;
        // },
        eventContent: function(arg) {
            const timeStr = formatEventTimeRange(arg.event.start, arg.event.end);
            const formattedTitle = formatEventTitle(arg.event.title);
            const htmlTitle = formattedTitle.replace(/\n/g, '<br>');
            return {
                html:
                    '<div class="fc-event-main-frame">' +
                    '<div class="fc-event-time">' + timeStr + '</div>' +
                    '<div class="fc-event-title">' + htmlTitle + '</div>' +
                    '</div>'
            };
        },
        eventMouseEnter: function(mouseEnterInfo) {
            const event = mouseEnterInfo.event;
            const el = mouseEnterInfo.el;
            const jsEvent = mouseEnterInfo.jsEvent;
            const timeStr = formatEventTimeRange(event.start, event.end);
            const formattedTitle = formatEventTitle(event.title);
            const htmlTitle = formattedTitle.replace(/\n/g, '<br>');
            
            let tooltip = document.getElementById('fc-event-tooltip');
            if (!tooltip) {
                tooltip = document.createElement('div');
                tooltip.id = 'fc-event-tooltip';
                tooltip.className = 'fc-event-tooltip';
                document.body.appendChild(tooltip);
            }
            
            tooltip.innerHTML =
                '<div class="fc-event-tooltip-title">' + (event.title || '') + '</div>' +
                '<div class="fc-event-tooltip-content">' + timeStr + '</div>';
            
            tooltip.style.display = 'block';
            
            const offset = 12;
            
            function positionAt(x, y) {
                tooltip.style.visibility = 'hidden';
                const w = tooltip.offsetWidth;
                const h = tooltip.offsetHeight;
                let left = x + offset;
                let top = y + offset;
                if (left + w > window.innerWidth - 10) left = x - w - offset;
                if (left < 10) left = 10;
                if (top + h > window.innerHeight - 10) top = y - h - offset;
                if (top < 10) top = 10;
                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
                tooltip.style.visibility = 'visible';
            }
            
            positionAt(jsEvent.clientX, jsEvent.clientY);
            
            const onMove = (e) => positionAt(e.clientX, e.clientY);
            el.addEventListener('mousemove', onMove);
            el.addEventListener('mouseleave', function leave() {
                el.removeEventListener('mousemove', onMove);
                el.removeEventListener('mouseleave', leave);
                tooltip.style.display = 'none';
            }, { once: true });
        },
        eventMouseLeave: function() {
            const tooltip = document.getElementById('fc-event-tooltip');
            if (tooltip) tooltip.style.display = 'none';
        },
    });

    calendar.render();
    console.log(events);
    return calendar;
};
