/**
 * Taxonomy Blocks
 * 
 * Consolidated React components for all taxonomy-related blocks.
 * Contains TaxonomyImageBlock, TaxonomyFaqsBlock, and network/channel management.
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    TextareaControl, 
    SelectControl,
    ToggleControl,
    Button,
    Placeholder,
    ResponsiveWrapper
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Taxonomy Image Block
 * 
 * Handles image management for taxonomies (networks, channels, genres, etc.)
 */
const TaxonomyImageBlock = {
    title: __('Taxonomy Image', 'tmu-theme'),
    icon: 'format-image',
    category: 'tmu-blocks',
    description: __('Taxonomy image management for channels, networks, and other taxonomies', 'tmu-theme'),
    keywords: [__('taxonomy', 'tmu-theme'), __('image', 'tmu-theme'), __('logo', 'tmu-theme'), __('network', 'tmu-theme')],
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
                        />
                        
                        <SelectControl
                            label={__('Taxonomy Item', 'tmu-theme')}
                            value={attributes.taxonomy_id}
                            options={taxonomyOptions}
                            onChange={(value) => setAttributes({ taxonomy_id: parseInt(value) })}
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
                </InspectorControls>
                
                <div className="tmu-taxonomy-image-block">
                    {attributes.image_id ? (
                        <div className="taxonomy-image-preview">
                            <img 
                                src={attributes.image_url}
                                alt={attributes.image_alt}
                                className="taxonomy-image"
                            />
                            <div className="image-meta">
                                <span className="image-type-badge">{attributes.image_type.toUpperCase()}</span>
                                <span className="taxonomy-type">{attributes.taxonomy_type.toUpperCase()}</span>
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
                                        <Button onClick={open} isPrimary>
                                            {__('Select Image', 'tmu-theme')}
                                        </Button>
                                    )}
                                />
                            </MediaUploadCheck>
                        </Placeholder>
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

/**
 * Taxonomy FAQs Block
 * 
 * Manages frequently asked questions for taxonomy pages
 */
const TaxonomyFaqsBlock = {
    title: __('Taxonomy FAQs', 'tmu-theme'),
    icon: 'editor-help',
    category: 'tmu-blocks',
    description: __('FAQ management for taxonomies', 'tmu-theme'),
    keywords: [__('faq', 'tmu-theme'), __('questions', 'tmu-theme'), __('taxonomy', 'tmu-theme'), __('help', 'tmu-theme')],
    supports: {
        html: false,
        multiple: true,
        reusable: true,
    },
    attributes: {
        taxonomy_type: { type: 'string', default: 'network' },
        taxonomy_id: { type: 'number', default: 0 },
        faqs: { type: 'array', default: [] },
        display_style: { type: 'string', default: 'accordion' },
        show_search: { type: 'boolean', default: false },
        max_display: { type: 'number', default: 10 },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const addFaq = () => {
            const newFaq = { 
                id: Date.now(),
                question: '', 
                answer: '',
                category: '',
                priority: 1
            };
            setAttributes({ faqs: [...attributes.faqs, newFaq] });
        };
        
        const updateFaq = (index, field, value) => {
            const updatedFaqs = [...attributes.faqs];
            updatedFaqs[index][field] = value;
            setAttributes({ faqs: updatedFaqs });
        };
        
        const removeFaq = (index) => {
            const updatedFaqs = attributes.faqs.filter((_, i) => i !== index);
            setAttributes({ faqs: updatedFaqs });
        };
        
        const moveFaq = (index, direction) => {
            const updatedFaqs = [...attributes.faqs];
            const newIndex = direction === 'up' ? index - 1 : index + 1;
            
            if (newIndex >= 0 && newIndex < updatedFaqs.length) {
                [updatedFaqs[index], updatedFaqs[newIndex]] = [updatedFaqs[newIndex], updatedFaqs[index]];
                setAttributes({ faqs: updatedFaqs });
            }
        };
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('FAQ Settings', 'tmu-theme')} initialOpen={true}>
                        <SelectControl
                            label={__('Taxonomy Type', 'tmu-theme')}
                            value={attributes.taxonomy_type}
                            options={[
                                { label: __('Network', 'tmu-theme'), value: 'network' },
                                { label: __('Channel', 'tmu-theme'), value: 'channel' },
                                { label: __('Genre', 'tmu-theme'), value: 'genre' },
                                { label: __('Country', 'tmu-theme'), value: 'country' },
                            ]}
                            onChange={(value) => setAttributes({ taxonomy_type: value })}
                        />
                        
                        <SelectControl
                            label={__('Display Style', 'tmu-theme')}
                            value={attributes.display_style}
                            options={[
                                { label: __('Accordion', 'tmu-theme'), value: 'accordion' },
                                { label: __('List', 'tmu-theme'), value: 'list' },
                                { label: __('Grid', 'tmu-theme'), value: 'grid' },
                                { label: __('Tabbed', 'tmu-theme'), value: 'tabbed' },
                            ]}
                            onChange={(value) => setAttributes({ display_style: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Search', 'tmu-theme')}
                            checked={attributes.show_search}
                            onChange={(value) => setAttributes({ show_search: value })}
                        />
                        
                        <Button isPrimary onClick={addFaq} className="add-faq-button">
                            {__('Add FAQ', 'tmu-theme')}
                        </Button>
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-taxonomy-faqs-block">
                    {attributes.faqs.length > 0 ? (
                        <div className="faq-list">
                            <div className="faq-header">
                                <h3>{__('Frequently Asked Questions', 'tmu-theme')}</h3>
                                <span className="faq-count">
                                    {attributes.faqs.length} {__('questions', 'tmu-theme')}
                                </span>
                            </div>
                            
                            {attributes.faqs.map((faq, index) => (
                                <div key={faq.id || index} className="faq-item">
                                    <div className="faq-controls">
                                        <Button
                                            onClick={() => moveFaq(index, 'up')}
                                            disabled={index === 0}
                                            isSmall
                                        >
                                            ↑
                                        </Button>
                                        <Button
                                            onClick={() => moveFaq(index, 'down')}
                                            disabled={index === attributes.faqs.length - 1}
                                            isSmall
                                        >
                                            ↓
                                        </Button>
                                        <Button
                                            onClick={() => removeFaq(index)}
                                            isDestructive
                                            isSmall
                                        >
                                            {__('Remove', 'tmu-theme')}
                                        </Button>
                                    </div>
                                    
                                    <TextControl
                                        label={__('Question', 'tmu-theme')}
                                        value={faq.question}
                                        onChange={(value) => updateFaq(index, 'question', value)}
                                        placeholder={__('Enter question...', 'tmu-theme')}
                                    />
                                    
                                    <TextareaControl
                                        label={__('Answer', 'tmu-theme')}
                                        value={faq.answer}
                                        onChange={(value) => updateFaq(index, 'answer', value)}
                                        placeholder={__('Enter answer...', 'tmu-theme')}
                                        rows={3}
                                    />
                                    
                                    <div className="faq-meta">
                                        <TextControl
                                            label={__('Category', 'tmu-theme')}
                                            value={faq.category || ''}
                                            onChange={(value) => updateFaq(index, 'category', value)}
                                            placeholder={__('Optional category', 'tmu-theme')}
                                        />
                                        
                                        <SelectControl
                                            label={__('Priority', 'tmu-theme')}
                                            value={faq.priority || 1}
                                            options={[
                                                { label: __('Low', 'tmu-theme'), value: 1 },
                                                { label: __('Medium', 'tmu-theme'), value: 2 },
                                                { label: __('High', 'tmu-theme'), value: 3 },
                                                { label: __('Critical', 'tmu-theme'), value: 4 },
                                            ]}
                                            onChange={(value) => updateFaq(index, 'priority', parseInt(value))}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <Placeholder
                            icon="editor-help"
                            label={__('Taxonomy FAQs', 'tmu-theme')}
                            instructions={__('Add frequently asked questions for this taxonomy.', 'tmu-theme')}
                        >
                            <Button isPrimary onClick={addFaq}>
                                {__('Add First FAQ', 'tmu-theme')}
                            </Button>
                        </Placeholder>
                    )}
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        return (
            <div className="tmu-taxonomy-faqs">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
};

/**
 * Network Management Block
 * 
 * Specialized block for TV network and channel management
 */
const NetworkManagementBlock = {
    title: __('Network Management', 'tmu-theme'),
    icon: 'networking',
    category: 'tmu-blocks',
    description: __('Network and channel management for TV content', 'tmu-theme'),
    keywords: [__('network', 'tmu-theme'), __('channel', 'tmu-theme'), __('tv', 'tmu-theme'), __('broadcast', 'tmu-theme')],
    supports: {
        html: false,
        multiple: false,
        reusable: false,
    },
    attributes: {
        network_id: { type: 'number', default: 0 },
        network_name: { type: 'string', default: '' },
        network_type: { type: 'string', default: 'broadcast' },
        country: { type: 'string', default: '' },
        language: { type: 'string', default: '' },
        website: { type: 'string', default: '' },
        logo_url: { type: 'string', default: '' },
        description: { type: 'string', default: '' },
        launch_date: { type: 'string', default: '' },
        owner: { type: 'string', default: '' },
        channels: { type: 'array', default: [] },
        programming_blocks: { type: 'array', default: [] },
        target_audience: { type: 'string', default: 'general' },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const addChannel = () => {
            const newChannel = {
                id: Date.now(),
                name: '',
                type: 'main',
                description: ''
            };
            setAttributes({ channels: [...attributes.channels, newChannel] });
        };
        
        const updateChannel = (index, field, value) => {
            const updatedChannels = [...attributes.channels];
            updatedChannels[index][field] = value;
            setAttributes({ channels: updatedChannels });
        };
        
        const removeChannel = (index) => {
            const updatedChannels = attributes.channels.filter((_, i) => i !== index);
            setAttributes({ channels: updatedChannels });
        };
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Network Information', 'tmu-theme')} initialOpen={true}>
                        <TextControl
                            label={__('Network Name', 'tmu-theme')}
                            value={attributes.network_name}
                            onChange={(value) => setAttributes({ network_name: value })}
                            placeholder={__('Enter network name', 'tmu-theme')}
                        />
                        
                        <SelectControl
                            label={__('Network Type', 'tmu-theme')}
                            value={attributes.network_type}
                            options={[
                                { label: __('Broadcast', 'tmu-theme'), value: 'broadcast' },
                                { label: __('Cable', 'tmu-theme'), value: 'cable' },
                                { label: __('Satellite', 'tmu-theme'), value: 'satellite' },
                                { label: __('Streaming', 'tmu-theme'), value: 'streaming' },
                                { label: __('Digital', 'tmu-theme'), value: 'digital' },
                                { label: __('Premium', 'tmu-theme'), value: 'premium' },
                            ]}
                            onChange={(value) => setAttributes({ network_type: value })}
                        />
                        
                        <TextControl
                            label={__('Country', 'tmu-theme')}
                            value={attributes.country}
                            onChange={(value) => setAttributes({ country: value })}
                            placeholder={__('Network country', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Primary Language', 'tmu-theme')}
                            value={attributes.language}
                            onChange={(value) => setAttributes({ language: value })}
                            placeholder={__('Primary broadcast language', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Website', 'tmu-theme')}
                            type="url"
                            value={attributes.website}
                            onChange={(value) => setAttributes({ website: value })}
                            placeholder={__('https://network-website.com', 'tmu-theme')}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Channel Management', 'tmu-theme')} initialOpen={false}>
                        <Button isPrimary onClick={addChannel}>
                            {__('Add Channel', 'tmu-theme')}
                        </Button>
                        
                        {attributes.channels.map((channel, index) => (
                            <div key={channel.id || index} className="channel-item">
                                <TextControl
                                    label={__('Channel Name', 'tmu-theme')}
                                    value={channel.name}
                                    onChange={(value) => updateChannel(index, 'name', value)}
                                />
                                <SelectControl
                                    label={__('Channel Type', 'tmu-theme')}
                                    value={channel.type}
                                    options={[
                                        { label: __('Main Channel', 'tmu-theme'), value: 'main' },
                                        { label: __('HD Channel', 'tmu-theme'), value: 'hd' },
                                        { label: __('Plus Channel', 'tmu-theme'), value: 'plus' },
                                        { label: __('Kids Channel', 'tmu-theme'), value: 'kids' },
                                        { label: __('News Channel', 'tmu-theme'), value: 'news' },
                                        { label: __('Sports Channel', 'tmu-theme'), value: 'sports' },
                                    ]}
                                    onChange={(value) => updateChannel(index, 'type', value)}
                                />
                                <Button
                                    isDestructive
                                    onClick={() => removeChannel(index)}
                                >
                                    {__('Remove Channel', 'tmu-theme')}
                                </Button>
                            </div>
                        ))}
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-network-management-block">
                    {attributes.network_name ? (
                        <div className="network-preview">
                            <div className="network-header">
                                <h3>{attributes.network_name}</h3>
                                <span className="network-type-badge">
                                    {attributes.network_type.toUpperCase()}
                                </span>
                            </div>
                            
                            <div className="network-info">
                                <p><strong>{__('Country:', 'tmu-theme')}</strong> {attributes.country}</p>
                                <p><strong>{__('Language:', 'tmu-theme')}</strong> {attributes.language}</p>
                                {attributes.channels.length > 0 && (
                                    <p><strong>{__('Channels:', 'tmu-theme')}</strong> {attributes.channels.length}</p>
                                )}
                            </div>
                            
                            {attributes.channels.length > 0 && (
                                <div className="channels-list">
                                    <h4>{__('Channels:', 'tmu-theme')}</h4>
                                    <ul>
                                        {attributes.channels.map((channel, index) => (
                                            <li key={channel.id || index}>
                                                {channel.name} <span className="channel-type">({channel.type})</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    ) : (
                        <Placeholder
                            icon="networking"
                            label={__('Network Management', 'tmu-theme')}
                            instructions={__('Configure network and channel information.', 'tmu-theme')}
                        />
                    )}
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        return (
            <div className="tmu-network-management">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
};

// Register all taxonomy blocks
registerBlockType('tmu/taxonomy-image', TaxonomyImageBlock);
registerBlockType('tmu/taxonomy-faqs', TaxonomyFaqsBlock);
registerBlockType('tmu/network-management', NetworkManagementBlock);

export { TaxonomyImageBlock, TaxonomyFaqsBlock, NetworkManagementBlock };