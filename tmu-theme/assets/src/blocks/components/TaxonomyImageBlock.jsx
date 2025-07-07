/**
 * Taxonomy Image Block
 * 
 * React component for taxonomy image block editor interface.
 * Handles taxonomy image uploads and metadata.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    TextareaControl, 
    SelectControl,
    Button,
    Placeholder,
    ResponsiveWrapper
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const TaxonomyImageBlock = {
    title: __('Taxonomy Image', 'tmu-theme'),
    icon: 'format-image',
    category: 'tmu-blocks',
    description: __('Taxonomy image management for channels, networks, and other taxonomies', 'tmu-theme'),
    keywords: [__('taxonomy', 'tmu-theme'), __('image', 'tmu-theme'), __('logo', 'tmu-theme')],
    supports: {
        html: false,
        multiple: true,
        reusable: true,
    },
    attributes: {
        taxonomy_type: { type: 'string', default: 'network' },
        taxonomy_id: { type: 'number', default: 0 },
        image_id: { type: 'number', default: 0 },
        image_url: { type: 'string', default: '' },
        image_alt: { type: 'string', default: '' },
        image_caption: { type: 'string', default: '' },
        image_type: { type: 'string', default: 'logo' },
        width: { type: 'number', default: 0 },
        height: { type: 'number', default: 0 },
        file_size: { type: 'number', default: 0 },
        mime_type: { type: 'string', default: '' },
        display_size: { type: 'string', default: 'medium' },
        alignment: { type: 'string', default: 'center' },
        link_url: { type: 'string', default: '' },
        link_target: { type: 'string', default: '_self' },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [validationErrors, setValidationErrors] = useState({});
        const [taxonomyOptions, setTaxonomyOptions] = useState([]);
        
        // Get available taxonomies
        const taxonomies = useSelect((select) => {
            const taxonomyTypes = ['network', 'channel', 'genre', 'country', 'language'];
            const allTerms = {};
            
            taxonomyTypes.forEach(taxonomy => {
                const terms = select('core').getEntityRecords('taxonomy', taxonomy, {
                    per_page: -1,
                    hide_empty: false,
                });
                if (terms) {
                    allTerms[taxonomy] = terms;
                }
            });
            
            return allTerms;
        }, []);
        
        useEffect(() => {
            if (taxonomies && attributes.taxonomy_type && taxonomies[attributes.taxonomy_type]) {
                const options = [
                    { label: __('Select Item', 'tmu-theme'), value: 0 },
                    ...taxonomies[attributes.taxonomy_type].map(term => ({
                        label: term.name,
                        value: term.id
                    }))
                ];
                setTaxonomyOptions(options);
            }
        }, [taxonomies, attributes.taxonomy_type]);
        
        // Get image details
        const imageData = useSelect((select) => {
            return attributes.image_id ? select('core').getMedia(attributes.image_id) : null;
        }, [attributes.image_id]);
        
        useEffect(() => {
            if (imageData) {
                setAttributes({
                    image_url: imageData.source_url,
                    image_alt: imageData.alt_text || '',
                    image_caption: imageData.caption?.rendered || '',
                    width: imageData.media_details?.width || 0,
                    height: imageData.media_details?.height || 0,
                    file_size: imageData.media_details?.filesize || 0,
                    mime_type: imageData.mime_type || '',
                });
            }
        }, [imageData]);
        
        const validateFields = () => {
            const errors = {};
            
            if (!attributes.taxonomy_type) {
                errors.taxonomy_type = __('Taxonomy type is required', 'tmu-theme');
            }
            
            if (!attributes.image_id) {
                errors.image_id = __('Image is required', 'tmu-theme');
            }
            
            if (attributes.link_url && !isValidUrl(attributes.link_url)) {
                errors.link_url = __('Please enter a valid URL', 'tmu-theme');
            }
            
            setValidationErrors(errors);
            return Object.keys(errors).length === 0;
        };
        
        const isValidUrl = (string) => {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        };
        
        const formatFileSize = (bytes) => {
            if (!bytes) return '';
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return `${(bytes / Math.pow(1024, i)).toFixed(2)} ${sizes[i]}`;
        };
        
        const onSelectImage = (media) => {
            setAttributes({
                image_id: media.id,
                image_url: media.url,
                image_alt: media.alt || '',
                image_caption: media.caption || '',
                width: media.width || 0,
                height: media.height || 0,
                file_size: media.filesizeInBytes || 0,
                mime_type: media.mime || '',
            });
        };
        
        const onRemoveImage = () => {
            setAttributes({
                image_id: 0,
                image_url: '',
                image_alt: '',
                image_caption: '',
                width: 0,
                height: 0,
                file_size: 0,
                mime_type: '',
            });
        };
        
        useEffect(() => {
            validateFields();
        }, [attributes]);
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Taxonomy Selection', 'tmu-theme')} initialOpen={true}>
                        <SelectControl
                            label={__('Taxonomy Type', 'tmu-theme')}
                            value={attributes.taxonomy_type}
                            options={[
                                { label: __('Network', 'tmu-theme'), value: 'network' },
                                { label: __('Channel', 'tmu-theme'), value: 'channel' },
                                { label: __('Genre', 'tmu-theme'), value: 'genre' },
                                { label: __('Country', 'tmu-theme'), value: 'country' },
                                { label: __('Language', 'tmu-theme'), value: 'language' },
                            ]}
                            onChange={(value) => setAttributes({ taxonomy_type: value, taxonomy_id: 0 })}
                            help={validationErrors.taxonomy_type}
                        />
                        
                        <SelectControl
                            label={__('Taxonomy Item', 'tmu-theme')}
                            value={attributes.taxonomy_id}
                            options={taxonomyOptions}
                            onChange={(value) => setAttributes({ taxonomy_id: parseInt(value) })}
                            help={__('Select the specific taxonomy item', 'tmu-theme')}
                        />
                        
                        <SelectControl
                            label={__('Image Type', 'tmu-theme')}
                            value={attributes.image_type}
                            options={[
                                { label: __('Logo', 'tmu-theme'), value: 'logo' },
                                { label: __('Banner', 'tmu-theme'), value: 'banner' },
                                { label: __('Icon', 'tmu-theme'), value: 'icon' },
                                { label: __('Background', 'tmu-theme'), value: 'background' },
                                { label: __('Thumbnail', 'tmu-theme'), value: 'thumbnail' },
                            ]}
                            onChange={(value) => setAttributes({ image_type: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Image Settings', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Alt Text', 'tmu-theme')}
                            value={attributes.image_alt}
                            onChange={(value) => setAttributes({ image_alt: value })}
                            help={__('Alternative text for accessibility', 'tmu-theme')}
                        />
                        
                        <TextareaControl
                            label={__('Caption', 'tmu-theme')}
                            value={attributes.image_caption}
                            onChange={(value) => setAttributes({ image_caption: value })}
                            rows={3}
                        />
                        
                        <SelectControl
                            label={__('Display Size', 'tmu-theme')}
                            value={attributes.display_size}
                            options={[
                                { label: __('Thumbnail', 'tmu-theme'), value: 'thumbnail' },
                                { label: __('Medium', 'tmu-theme'), value: 'medium' },
                                { label: __('Large', 'tmu-theme'), value: 'large' },
                                { label: __('Full Size', 'tmu-theme'), value: 'full' },
                            ]}
                            onChange={(value) => setAttributes({ display_size: value })}
                        />
                        
                        <SelectControl
                            label={__('Alignment', 'tmu-theme')}
                            value={attributes.alignment}
                            options={[
                                { label: __('Left', 'tmu-theme'), value: 'left' },
                                { label: __('Center', 'tmu-theme'), value: 'center' },
                                { label: __('Right', 'tmu-theme'), value: 'right' },
                            ]}
                            onChange={(value) => setAttributes({ alignment: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Link Settings', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Link URL', 'tmu-theme')}
                            value={attributes.link_url}
                            onChange={(value) => setAttributes({ link_url: value })}
                            placeholder={__('https://example.com', 'tmu-theme')}
                            help={validationErrors.link_url}
                        />
                        
                        <SelectControl
                            label={__('Link Target', 'tmu-theme')}
                            value={attributes.link_target}
                            options={[
                                { label: __('Same Window', 'tmu-theme'), value: '_self' },
                                { label: __('New Window', 'tmu-theme'), value: '_blank' },
                            ]}
                            onChange={(value) => setAttributes({ link_target: value })}
                        />
                    </PanelBody>
                    
                    {attributes.image_id > 0 && (
                        <PanelBody title={__('Image Information', 'tmu-theme')} initialOpen={false}>
                            <div className="image-info text-sm space-y-2">
                                {attributes.width && attributes.height && (
                                    <div>
                                        <strong>{__('Dimensions:', 'tmu-theme')}</strong> {attributes.width} × {attributes.height}px
                                    </div>
                                )}
                                {attributes.file_size > 0 && (
                                    <div>
                                        <strong>{__('File Size:', 'tmu-theme')}</strong> {formatFileSize(attributes.file_size)}
                                    </div>
                                )}
                                {attributes.mime_type && (
                                    <div>
                                        <strong>{__('Type:', 'tmu-theme')}</strong> {attributes.mime_type}
                                    </div>
                                )}
                            </div>
                        </PanelBody>
                    )}
                </InspectorControls>
                
                <div className="tmu-taxonomy-image-block">
                    {attributes.image_id ? (
                        <div className={`tmu-taxonomy-image-preview text-${attributes.alignment}`}>
                            <div className="image-container relative inline-block">
                                {attributes.link_url ? (
                                    <a 
                                        href={attributes.link_url} 
                                        target={attributes.link_target}
                                        className="block"
                                    >
                                        <img 
                                            src={attributes.image_url}
                                            alt={attributes.image_alt}
                                            className={`taxonomy-image ${attributes.display_size === 'thumbnail' ? 'max-w-32' : 
                                                attributes.display_size === 'medium' ? 'max-w-64' :
                                                attributes.display_size === 'large' ? 'max-w-96' : 'max-w-full'} h-auto`}
                                        />
                                    </a>
                                ) : (
                                    <img 
                                        src={attributes.image_url}
                                        alt={attributes.image_alt}
                                        className={`taxonomy-image ${attributes.display_size === 'thumbnail' ? 'max-w-32' : 
                                            attributes.display_size === 'medium' ? 'max-w-64' :
                                            attributes.display_size === 'large' ? 'max-w-96' : 'max-w-full'} h-auto`}
                                    />
                                )}
                                
                                <div className="image-controls absolute top-2 right-2 opacity-0 hover:opacity-100 transition-opacity">
                                    <MediaUploadCheck>
                                        <MediaUpload
                                            onSelect={onSelectImage}
                                            allowedTypes={['image']}
                                            value={attributes.image_id}
                                            render={({ open }) => (
                                                <Button
                                                    onClick={open}
                                                    className="bg-blue-600 text-white text-xs px-2 py-1 rounded mr-1"
                                                >
                                                    {__('Replace', 'tmu-theme')}
                                                </Button>
                                            )}
                                        />
                                    </MediaUploadCheck>
                                    
                                    <Button
                                        onClick={onRemoveImage}
                                        className="bg-red-600 text-white text-xs px-2 py-1 rounded"
                                    >
                                        {__('Remove', 'tmu-theme')}
                                    </Button>
                                </div>
                            </div>
                            
                            {attributes.image_caption && (
                                <div className="image-caption text-sm text-gray-600 mt-2">
                                    {attributes.image_caption}
                                </div>
                            )}
                            
                            <div className="image-meta mt-3 p-3 bg-gray-50 rounded text-sm">
                                <div className="flex items-center justify-between mb-2">
                                    <span className={`image-type-badge ${
                                        attributes.image_type === 'logo' ? 'bg-blue-100 text-blue-800' :
                                        attributes.image_type === 'banner' ? 'bg-green-100 text-green-800' :
                                        attributes.image_type === 'icon' ? 'bg-purple-100 text-purple-800' :
                                        'bg-gray-100 text-gray-800'
                                    } text-xs font-medium px-2 py-1 rounded`}>
                                        {attributes.image_type.replace('_', ' ').toUpperCase()}
                                    </span>
                                    <span className="taxonomy-type text-xs text-gray-500">
                                        {attributes.taxonomy_type.toUpperCase()}
                                    </span>
                                </div>
                                {taxonomyOptions.find(opt => opt.value === attributes.taxonomy_id) && (
                                    <div className="taxonomy-name font-medium text-gray-700">
                                        {taxonomyOptions.find(opt => opt.value === attributes.taxonomy_id).label}
                                    </div>
                                )}
                            </div>
                        </div>
                    ) : (
                        <Placeholder
                            icon="format-image"
                            label={__('Taxonomy Image', 'tmu-theme')}
                            instructions={__('Upload an image for the selected taxonomy item.', 'tmu-theme')}
                        >
                            <MediaUploadCheck>
                                <MediaUpload
                                    onSelect={onSelectImage}
                                    allowedTypes={['image']}
                                    value={attributes.image_id}
                                    render={({ open }) => (
                                        <Button
                                            onClick={open}
                                            isPrimary
                                            disabled={!attributes.taxonomy_type}
                                        >
                                            {__('Select Image', 'tmu-theme')}
                                        </Button>
                                    )}
                                />
                            </MediaUploadCheck>
                        </Placeholder>
                    )}
                    
                    {Object.keys(validationErrors).length > 0 && (
                        <div className="validation-errors mt-4 p-3 bg-red-50 border border-red-200 rounded">
                            <div className="text-red-700 text-sm font-medium mb-1">{__('Please fix the following errors:', 'tmu-theme')}</div>
                            <ul className="text-red-600 text-xs list-disc list-inside">
                                {Object.values(validationErrors).map((error, index) => (
                                    <li key={index}>{error}</li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        return (
            <div className="tmu-taxonomy-image">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
};

export default TaxonomyImageBlock;