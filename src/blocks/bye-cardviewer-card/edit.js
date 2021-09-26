import { useBlockProps } from '@wordpress/block-editor';

const _siteUrl = 'https://bye-project.xyz'; //better replace this with a get_site_url passed in from PHP
const blockStyle = {
    backgroundColor: '#900',
    color: '#fff',
    padding: '20px',
};

export const edit = function ({attributes, setAttributes}) {
    const blockProps = useBlockProps({style: blockStyle});

    return <div {...blockProps}>
        <input {...{
            placeholder: 'Expansion', value: attributes.expansion, onChange: function () {
                setAttributes({expansion: event.target.value})
            }
        }}/>
        <input {...{
            placeholder: 'CardID', value: attributes.cardId, onChange: function () {
                setAttributes({cardId: event.target.value})
            }
        }}/>
        <input {...{
            placeholder: 'Version', value: attributes.version, onChange: function () {
                setAttributes({version: event.target.value})
            }
        }}/>
        <img {...{src: _siteUrl + '/wp-content/uploads/cards/' + attributes.expansion + '/' + attributes.version + '/' + attributes.cardId + '.png'}}
             alt="Preview Image"/>
    </div>
}