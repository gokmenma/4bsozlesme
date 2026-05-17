/**
 * Flatpickr Global Initialization
 */

document.addEventListener('DOMContentLoaded', function() {
    // Turkish Localization
    const Turkish = {
        firstDayOfWeek: 1,
        weekdays: {
            shorthand: ["Paz", "Pzt", "Sal", "Çar", "Per", "Cum", "Cmt"],
            longhand: [
                "Pazar",
                "Pazartesi",
                "Salı",
                "Çarşamba",
                "Perşembe",
                "Cuma",
                "Cumartesi",
            ],
        },
        months: {
            shorthand: [
                "Oca",
                "Şub",
                "Mar",
                "Nis",
                "May",
                "Haz",
                "Tem",
                "Ağu",
                "Eyl",
                "Eki",
                "Kas",
                "Ara",
            ],
            longhand: [
                "Ocak",
                "Şubat",
                "Mart",
                "Nisan",
                "Mayıs",
                "Haziran",
                "Temmuz",
                "Ağustos",
                "Eylül",
                "Ekim",
                "Kasım",
                "Aralık",
            ],
        },
        rangeSeparator: " - ",
        amPM: ["ÖÖ", "ÖS"],
        time_24hr: true,
    };

    // Initialize all datepickers
    const initFlatpickr = () => {
        const commonConfig = {
            locale: Turkish,
            allowInput: true,
            disableMobile: "true",
            animate: true,
            // Tamamen pasif haftaları gizleme mantığı
            onMonthChange: function(selectedDates, dateStr, instance) {
                setTimeout(() => hidePassiveWeeks(instance), 0);
            },
            onReady: function(selectedDates, dateStr, instance) {
                hidePassiveWeeks(instance);
            }
        };

        function hidePassiveWeeks(instance) {
            const days = instance.days.querySelectorAll('.flatpickr-day');
            if (!days.length) return;

            // 6 haftayı da kontrol et (her hafta 7 gün)
            for (let week = 0; week < 6; week++) {
                let isPassiveWeek = true;
                for (let dayIdx = 0; dayIdx < 7; dayIdx++) {
                    const idx = week * 7 + dayIdx;
                    const day = days[idx];
                    if (day && !day.classList.contains('prevMonthDay') && !day.classList.contains('nextMonthDay')) {
                        isPassiveWeek = false;
                        break;
                    }
                }

                // Eğer hafta tamamen pasifse (başka aya aitse) gizle
                for (let dayIdx = 0; dayIdx < 7; dayIdx++) {
                    const idx = week * 7 + dayIdx;
                    const day = days[idx];
                    if (day) {
                        day.style.display = isPassiveWeek ? 'none' : 'flex';
                    }
                }
            }
            
            // Takvim kutusunun yüksekliğini içeriğe göre ayarla
            instance.calendarContainer.style.height = 'auto';
        }

        // Her bir input için özel kontrol yaparak başlatalım
        document.querySelectorAll(".datepicker, .datetimepicker").forEach(el => {
            const isDateTime = el.classList.contains('datetimepicker');
            const dialog = el.closest('dialog');
            
            const config = {
                ...commonConfig,
                dateFormat: isDateTime ? "d.m.Y H:i" : "d.m.Y",
                enableTime: isDateTime,
                time_24hr: isDateTime,
                static: dialog ? true : false,
                appendTo: dialog ? dialog : undefined
            };

            flatpickr(el, config);
        });
    };

    initFlatpickr();

    // Re-initialize for dynamically added elements (if any)
    // You can call window.initFlatpickr() manually if needed
    window.initFlatpickr = initFlatpickr;
});
