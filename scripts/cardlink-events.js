function show_cardlink(event) {
    event.target.dataset.hover = 'true';
    jQuery.get(`/wp-json/bye/v1/cardblock-renderer?cardId=${event.target.dataset.cardid}&version=${event.target.dataset.version}`, (res) => {
        if(event.target.dataset.hover !== 'true') {
            return;
        }

        let template = document.createElement('template');
        template.innerHTML = res.rendered.trim();

        let block = template.content.firstChild;
        block.classList.add('bye-card-tooltip');
        document.body.appendChild(block);

        //Calculate position after appending so we know the block size
        let linktop = event.target.getBoundingClientRect().top;
        let linkright = event.target.getBoundingClientRect().right;
        let blockheight = block.getBoundingClientRect().height;
        let blockwidth = block.getBoundingClientRect().width;
        block.style.left = `${
            Math.max(50,
                Math.min(window.innerWidth - (blockwidth + 50), linkright + 50)
            )
        }px`;
        block.style.top = `${
            Math.max(50,
                Math.min(window.innerHeight - (blockheight + 50), linktop - blockheight/2)
            )
        }px`;
    })
}

function hide_cardlink(event) {
    event.target.dataset.hover = 'false';
    let tt=document.querySelector('.bye-card-tooltip');
    while (tt) {
        tt.remove();
        tt=document.querySelector('.bye-card-tooltip')
    }
}