import {useBlockProps, InspectorControls} from '@wordpress/block-editor';
import {PanelBody} from '@wordpress/components';
import {useState, useEffect} from '@wordpress/element';

const _siteUrl = 'https://bye-project.xyz'; //better replace this with a get_site_url passed in from PHP

export const edit = function ({attributes, setAttributes}) {
    const blockProps = useBlockProps({style: {backgroundColor: '#000'}});

    const [expansions, setExpansions] = useState([]);
    const [cards, setCards] = useState([]);
    const [imgUrl, setImgUrl] = useState('');

    const selectedCard = cards.find(c => c.code === attributes.cardId);

    function updateExpansionsList() {
        wp.apiFetch({path: 'bye/v1/expansions'}).then(data => {
            setExpansions(data);
        }, error => {
            console.log([attributes.cardId, error]);
        })
    }

    function updateCardsList() {
        const versionParam = attributes.version
            ? '?max_version=' + attributes.version
            : '';
        wp.apiFetch({path: 'bye/v1/cards/' + attributes.expansion + versionParam})
            .then(data => {
                setCards(data.sort((a, b) => a.code - b.code));
            }, error => {
                console.log([attributes.cardId, error])
            });
    }

    useEffect(() => {
        updateExpansionsList();
    }, [])
    useEffect(() => {
        updateCardsList();
    }, [attributes.expansion, attributes.version]);
    useEffect(() => {
        let _imgUrl = _siteUrl + '/wp-content/uploads/cards/' + selectedCard?.version + '/' + attributes.expansion + '/en/' + attributes.cardId + '.png';
        let testImage = new Image();
        testImage.onload = () => {
            setImgUrl(_imgUrl);
        }
        testImage.onerror = () => {
            setImgUrl(_imgUrl.substring(0, _imgUrl.length - 4) + '.jpg');
        }
        testImage.src = _imgUrl;

    }, [attributes.expansion, attributes.cardId, attributes.version])

    return <div {...blockProps}>
        <InspectorControls>
            <PanelBody title={'Card Selection'} initialOpen={true}>
                <fieldset>
                    <legend>Expansion</legend>
                    <select {...{
                        value: attributes.expansion,
                        disabled: (expansions.length === 0),
                        onChange: function (event) {
                            setAttributes({expansion: event.target.value})
                        }
                    }}>
                        {expansions.map((expansion) => {
                            return <option {...{value: expansion.code}}>{expansion.name}</option>
                        })}
                    </select>
                </fieldset>
                <fieldset>
                    <legend>Card</legend>
                    <select {...{
                        value: attributes.cardId,
                        disabled: (cards.length === 0),
                        onChange: function (event) {
                            setAttributes({cardId: event.target.value})
                        }
                    }}>
                        {cards.map((card) => {
                            return <option {...{value: card.code}}>{card.name}</option>
                        })}
                    </select>
                </fieldset>
                <fieldset>
                    <legend>Max. Version</legend>
                    <input {...{
                        value: attributes.version, onChange: function (event) {
                            setAttributes({version: event.target.value.trim().length > 0 ? event.target.value : null})
                        }
                    }}/>
                </fieldset>
                <button onClick={() => {
                    updateExpansionsList();
                    updateCardsList();
                }}>Reload</button>
            </PanelBody>
        </InspectorControls>
        <img className="bye-card-image" src={imgUrl}
             alt="Preview Image"/>
        <h2 className="bye-card-cardname">{selectedCard?.name ?? ''}</h2>
        <p className="bye-card-cardtext">
            <span>{selectedCard?.description ?? ''}</span>
        </p>
    </div>
};