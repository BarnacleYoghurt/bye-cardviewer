function update_cardviewer_cardlist(event) {
    const blockId = event.target.id.split('-')[1];
    // We don't want to accidentally click the image link behind the dropdown, so block this immediately
    jQuery(`#bye-cardviewer-card-${blockId}`).css('pointer-events','none');
    jQuery.get(`/wp-json/bye/v1/cards/${event.target.options[event.target.selectedIndex].value}`)
        .done((res) => {
            // Card reload does its own click-blocking, so turn off ours just in case the change event doesn't get handled
            jQuery(`#bye-cardviewer-card-${blockId}`).css('pointer-events','auto');
            jQuery(`#c_card-${blockId}`).empty()
                .append(
                    res.sort((a, b) => a.code - b.code)
                       .map((c) => `<option value="${c.code}">${c.name}</option>`)
                )
                .trigger('change');
        })
        .fail((err) => {
            console.log(err);
            jQuery(`#bye-cardviewer-card-${blockId}`).css('pointer-events','auto');
            jQuery(`#c_card-${blockId}`).empty();
        });
}

function update_cardviewer_card(event) {
    const cardChanged = event.target.id.startsWith('c_card'); //Reset version and language in this case
    const blockId = event.target.id.split('-')[1];
    const cardSelect = jQuery(`#c_card-${blockId}`);
    const versionSelect = jQuery(`#c_version-${blockId}`);
    const langSelect = jQuery(`#c_lang-${blockId}`);

    const block = jQuery(`#bye-cardviewer-card-${blockId}`);
    const cardId = cardSelect.length > 0
        ? cardSelect[0].options[cardSelect[0].selectedIndex].value
        : block.data('cardid');
    const version = versionSelect.length > 0
        ? versionSelect[0].options[versionSelect[0].selectedIndex].value
        : undefined;
    const lang = langSelect.length > 0
        ? langSelect[0].options[langSelect[0].selectedIndex].value
        : undefined;
    block.css('pointer-events','none');
    jQuery.get(`/wp-json/bye/v1/cardblock-renderer?cardId=${cardId}` +
        (version && !cardChanged ? `&version=${version}` : '') +
        (lang && !cardChanged ? `&language=${lang}` : '') +
        `&selectableCard=${cardSelect.length}` +
        `&selectableVersion=${versionSelect.length}` +
        `&selectableLanguage=${langSelect.length}` +
        `&blockId=${blockId}`)
        .done((res) => {
            jQuery(`#bye-cardviewer-card-${blockId}`).replaceWith(res.rendered);
            jQuery(`#${event.target.id}`).focus(); // Refocus so we can scroll with arrow keys uninterrupted
        })
        .fail((err) => {
            console.log(err);
            jQuery(`#bye-cardviewer-card-${blockId}`).css('pointer-events','auto');
        });
}