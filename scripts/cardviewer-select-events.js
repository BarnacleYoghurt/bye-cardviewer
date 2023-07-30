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
    const blockId = event.target.id.split('-')[1];
    const cardId = event.target.options[event.target.selectedIndex].value;
    jQuery(`#bye-cardviewer-card-${blockId}`).css('pointer-events','none');
    jQuery.get(`/wp-json/bye/v1/cardblock-renderer?cardId=${cardId}&selectable=1&blockId=${blockId}`)
        .done((res) => {
            jQuery(`#bye-cardviewer-card-${blockId}`).replaceWith(res.rendered);
            jQuery(`#c_card-${blockId}`).focus(); // Refocus so we can scroll with arrow keys uninterrupted
        })
        .fail((err) => {
            console.log(err);
            jQuery(`#bye-cardviewer-card-${blockId}`).css('pointer-events','auto');
        });
}