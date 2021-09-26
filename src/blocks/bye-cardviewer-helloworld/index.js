import { useBlockProps } from '@wordpress/block-editor';

const blockStyle = {
    color: '#fff',
};
export const name = 'bye-cardviewer/helloworld';
export const settings = {
    edit: function () {
        const blockProps = useBlockProps({style: blockStyle});

        return <div {...blockProps}>
            Hello World from the editor!
        </div>
    },
    save: function () {
        return <div>
            Hello World!
        </div>
    }
};

