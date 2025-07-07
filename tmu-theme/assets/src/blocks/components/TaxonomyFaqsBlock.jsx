/**
 * Taxonomy FAQs Block
 * 
 * Simple React component for taxonomy FAQs management.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    TextareaControl, 
    SelectControl,
    Button,
    Placeholder
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const TaxonomyFaqsBlock = {
    title: __('Taxonomy FAQs', 'tmu-theme'),
    icon: 'editor-help',
    category: 'tmu-blocks',
    description: __('FAQ management for taxonomies', 'tmu-theme'),
    keywords: [__('faq', 'tmu-theme'), __('questions', 'tmu-theme'), __('taxonomy', 'tmu-theme')],
    attributes: {
        taxonomy_type: { type: 'string', default: 'network' },
        taxonomy_id: { type: 'number', default: 0 },
        faqs: { type: 'array', default: [] },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const addFaq = () => {
            const newFaq = { question: '', answer: '' };
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
                        
                        <Button isPrimary onClick={addFaq}>
                            {__('Add FAQ', 'tmu-theme')}
                        </Button>
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-taxonomy-faqs-block">
                    {attributes.faqs.length > 0 ? (
                        <div className="faq-list space-y-4">
                            {attributes.faqs.map((faq, index) => (
                                <div key={index} className="faq-item border border-gray-200 rounded p-4">
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
                                    <Button
                                        isDestructive
                                        onClick={() => removeFaq(index)}
                                        className="mt-2"
                                    >
                                        {__('Remove', 'tmu-theme')}
                                    </Button>
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

export default TaxonomyFaqsBlock;