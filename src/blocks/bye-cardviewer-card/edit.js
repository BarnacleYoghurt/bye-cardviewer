import {useBlockProps, InspectorControls} from '@wordpress/block-editor';
import {PanelBody, ToggleControl, TextControl, SelectControl, Button} from '@wordpress/components';
import {useState, useEffect} from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

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
            ? '&max_version=' + attributes.version
            : '';
        const langParam = attributes.language
            ? '&lang=' + attributes.language
            : '';
        wp.apiFetch({path: 'bye/v1/cards/' + attributes.expansion + '?' + versionParam + langParam})
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
    }, [attributes.expansion, attributes.version, attributes.language]);

    return <div {...blockProps}>
        <InspectorControls>
            <PanelBody title={'Card Selection'} initialOpen={true}>
                <fieldset>
                    <ToggleControl {...{
                        label: "Card of the Day?",
                        help: "Display random card of the day instead of a specific card",
                        checked: attributes.cardOfTheDay,
                        onChange: function(event) {
                                setAttributes({cardOfTheDay: !attributes.cardOfTheDay})
                            }
                        }}>
                    </ToggleControl>
                </fieldset>
                <fieldset>
                    <SelectControl {...{
                        label: "Expansion",
                        value: attributes.expansion,
                        options: expansions.map((expansion) => ({value: expansion.code, label: expansion.name})),
                        disabled: (expansions.length === 0 || attributes.cardOfTheDay),
                        onChange: function (value) {
                            setAttributes({expansion: value})
                        }
                    }}/>
                </fieldset>
                <fieldset>
                    <SelectControl {...{
                        label: "Card",
                        value: attributes.cardId,
                        disabled: (cards.length === 0 || attributes.cardOfTheDay),
                        options: cards.map((card) => ({value: card.code, label: card.name})),
                        onChange: function (value) {
                            setAttributes({cardId: value})
                        }
                    }}/>
                </fieldset>
                <fieldset>
                    <TextControl {...{
                        label: "Max. Version",
                        disabled:  attributes.cardOfTheDay,
                        value: attributes.version,
                        onChange: function (value) {
                            setAttributes({version: value.trim().length > 0 ? value : null})
                        }
                    }}/>
                </fieldset>
                <fieldset>
                    <TextControl {...{
                        label: "Language",
                        value: attributes.language,
                        onChange: function (value) {
                            setAttributes({language: value.trim().length > 0 ? value : null})
                        }
                    }}/>
                </fieldset>
                <Button {...{
                    text: "Reload Lists",
                    variant: "secondary",
                    onClick: () => {
                        updateExpansionsList();
                        updateCardsList();
                    }
                }}/>
            </PanelBody>
            <PanelBody title={'URL Parameters'} initialOpen={false}>
                <fieldset>
                    <ToggleControl {...{
                        label: "From URL params?",
                        help: "Specify card to display in URL parameters",
                        checked: attributes.fromUrlParams,
                        onChange: function(event) {
                            setAttributes({fromUrlParams: !attributes.fromUrlParams})
                        }
                    }}>
                    </ToggleControl>
                    <TextControl {...{
                        label: "Card ID param",
                        help: "Name of (mandatory) card ID URL parameter",
                        value: attributes.urlParamCardId,
                        onChange: function (event) {
                            setAttributes({urlParamCardId: event.target.value})
                        }
                    }}/>
                    <TextControl {...{
                        label: "Version param",
                        help: "Name of (optional) max. version parameter",
                        value: attributes.urlParamVersion,
                        onChange: function (event) {
                            setAttributes({urlParamVersion: event.target.value})
                        }
                    }}/>
                </fieldset>
            </PanelBody>
        </InspectorControls>
        <ServerSideRender block="bye-cardviewer/card" attributes={ attributes }/>
    </div>
};