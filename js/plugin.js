

jQuery(document).ready(function($) {

    let sc = document.createElement('div');
    sc.classList.add('autocomplete-suggestions');
    sc.setAttribute('autocomplete', 'off');

    let timer = null, rangeStart = null, rangeEnd = null, textarea = null;

    $(this).on('keyup', '.wc_comment', (e) => {
        textarea = e.target;

        let re = /(?<=@(\w+))/ig;
        re.lastIndex = textarea.selectionEnd;

        sc.remove();

        // @USER is typed
        if (re.test(textarea.value)) {

            let match = re.exec(textarea.value);
            let search = match[1];

            rangeEnd = match.index;
            rangeStart = rangeEnd - search.length - 1;

            clearTimeout(timer);
            timer = setTimeout(async () => {
                suggest(await source(search));
                sc = textarea.insertAdjacentElement("afterend", sc);
            }, 150);

        }
    });

    $(this).on('blur', '.wc_comment', () => {
        textarea = null;
        sc.remove();
    });


    $(sc).on('mousedown', (e) => {
        e.preventDefault();

        if (e.target.classList.contains('autocomplete-suggestion')) {

            let replace = "[" + e.target.dataset.val + "]";

            textarea.setRangeText(replace, rangeStart, rangeEnd);
            textarea.selectionStart = rangeStart + replace.length + 1;

            sc.remove();
        }
    });




    function source(search) {
        return fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=custom_mention&search=' + search,
        }).then(res => res.json());
    }

    function suggest(data){
        if (data.length) {
            sc.innerHTML = '';
            sc.append(...data.map(renderItem));
        }
    }

    function renderItem(item){
        let div = document.createElement('div');

        div.classList.add('autocomplete-suggestion');
        div.dataset.val = item['display_name'];
        div.innerHTML = `<span style="pointer-events: none"><b>@${item['display_name']}</b>&emsp;${item['user_nicename']}</span>`;

        return div;
    }

});
