import {useBlockProps} from '@wordpress/block-editor';
import {useState, useEffect} from '@wordpress/element';

const _siteUrl = 'https://bye-project.xyz'; //better replace this with a get_site_url passed in from PHP
const blockStyle = {
    backgroundColor: '#900',
    color: '#fff',
    padding: '20px',
};

export const edit = function ({attributes, setAttributes}) {
    const blockProps = useBlockProps({style: blockStyle});

    let imgUrl = _siteUrl + '/wp-content/uploads/cards/' + attributes.expansion + '/' + attributes.version + '/' + attributes.cardId + '.png';
    let testImage = new Image();
    testImage.src = imgUrl;
    if (testImage.width === 0) {
        imgUrl = imgUrl.substring(0, imgUrl.length - 4) + '.jpg';
    }

    const [expansions, setExpansions] = useState([]);
    const [cards, setCards] = useState([]);

    useEffect(() => {
        wp.apiFetch({path: 'bye/v1/expansions'}).then(data => {
            setExpansions(data);
        }, error => { console.log(attributes.cardId + " / " + error) });
        if (attributes.expansion) {
            wp.apiFetch({path: 'bye/v1/cards/' + attributes.expansion}).then(data => {
                setCards(data.sort((a,b) => a.code - b.code));
            }, error => { console.log(attributes.cardId + " / " + error) })
        }
    }, []);

    return <div {...blockProps}>
        <select {...{
            placeholder: 'Expansion', value: attributes.expansion, disabled: (expansions.length === 0), onChange: function (event) {
                setAttributes({expansion: event.target.value})
                wp.apiFetch({path: 'bye/v1/cards/' + event.target.value}).then(data => {
                    setCards(data.sort((a,b) => a.code - b.code));
                })
            }
        }}>
            {expansions.map((expansion) => {
                return <option {...{value: expansion.code}}>{expansion.name}</option>
            })}
        </select>
        <select {...{
            placeholder: 'CardID', value: attributes.cardId, disabled: (cards.length === 0), onChange: function (event) {
                setAttributes({cardId: event.target.value})
            }
        }}>
            {cards.map((card) => {
                return <option {...{value: card.code}}>{card.name}</option>
            })}
        </select>
        <input {...{
            placeholder: 'Max. version', value: attributes.version, onChange: function (event) {
                setAttributes({version: event.target.value})
            }
        }}/>
        <img {...{src: imgUrl}}
             alt="Preview Image"/>
    </div>
};