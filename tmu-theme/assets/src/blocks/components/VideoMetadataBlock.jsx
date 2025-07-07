/**
 * Video Metadata Block
 * 
 * React component for video metadata block editor interface.
 * Handles video content data fields.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    TextareaControl, 
    NumberControl,
    SelectControl,
    ToggleControl,
    Button,
    Placeholder
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const VideoMetadataBlock = {
    title: __('Video Metadata', 'tmu-theme'),
    icon: 'video-alt',
    category: 'tmu-blocks',
    description: __('Video content metadata management', 'tmu-theme'),
    keywords: [__('video', 'tmu-theme'), __('metadata', 'tmu-theme'), __('media', 'tmu-theme')],
    supports: {
        html: false,
        multiple: false,
        reusable: false,
    },
    attributes: {
        title: { type: 'string', default: '' },
        description: { type: 'string', default: '' },
        video_url: { type: 'string', default: '' },
        thumbnail_url: { type: 'string', default: '' },
        duration: { type: 'number', default: 0 },
        video_type: { type: 'string', default: 'trailer' },
        quality: { type: 'string', default: 'HD' },
        language: { type: 'string', default: '' },
        subtitles: { type: 'array', default: [] },
        file_size: { type: 'number', default: 0 },
        format: { type: 'string', default: 'mp4' },
        resolution: { type: 'string', default: '1920x1080' },
        bitrate: { type: 'number', default: 0 },
        framerate: { type: 'number', default: 24 },
        audio_codec: { type: 'string', default: 'AAC' },
        video_codec: { type: 'string', default: 'H.264' },
        upload_date: { type: 'string', default: '' },
        views: { type: 'number', default: 0 },
        likes: { type: 'number', default: 0 },
        featured: { type: 'boolean', default: false },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [validationErrors, setValidationErrors] = useState({});
        
        const validateFields = () => {
            const errors = {};
            
            if (!attributes.title.trim()) {
                errors.title = __('Video title is required', 'tmu-theme');
            }
            
            if (attributes.video_url && !isValidUrl(attributes.video_url)) {
                errors.video_url = __('Please enter a valid video URL', 'tmu-theme');
            }
            
            if (attributes.duration < 0) {
                errors.duration = __('Duration cannot be negative', 'tmu-theme');
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
        
        const formatDuration = (seconds) => {
            if (!seconds) return '';
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        };
        
        const formatFileSize = (bytes) => {
            if (!bytes) return '';
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return `${(bytes / Math.pow(1024, i)).toFixed(2)} ${sizes[i]}`;
        };
        
        useEffect(() => {
            validateFields();
        }, [attributes]);
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Video Information', 'tmu-theme')} initialOpen={true}>
                        <TextControl
                            label={__('Video Title', 'tmu-theme')}
                            value={attributes.title}
                            onChange={(value) => setAttributes({ title: value })}
                            placeholder={__('Enter video title', 'tmu-theme')}
                            help={validationErrors.title}
                        />
                        
                        <TextareaControl
                            label={__('Description', 'tmu-theme')}
                            value={attributes.description}
                            onChange={(value) => setAttributes({ description: value })}
                            placeholder={__('Video description...', 'tmu-theme')}
                            rows={4}
                        />
                        
                        <SelectControl
                            label={__('Video Type', 'tmu-theme')}
                            value={attributes.video_type}
                            options={[
                                { label: __('Trailer', 'tmu-theme'), value: 'trailer' },
                                { label: __('Teaser', 'tmu-theme'), value: 'teaser' },
                                { label: __('Behind the Scenes', 'tmu-theme'), value: 'behind_scenes' },
                                { label: __('Interview', 'tmu-theme'), value: 'interview' },
                                { label: __('Featurette', 'tmu-theme'), value: 'featurette' },
                                { label: __('Clip', 'tmu-theme'), value: 'clip' },
                                { label: __('Bloopers', 'tmu-theme'), value: 'bloopers' },
                                { label: __('Opening Credits', 'tmu-theme'), value: 'opening' },
                                { label: __('Other', 'tmu-theme'), value: 'other' },
                            ]}
                            onChange={(value) => setAttributes({ video_type: value })}
                        />
                        
                        <ToggleControl
                            label={__('Featured Video', 'tmu-theme')}
                            checked={attributes.featured}
                            onChange={(value) => setAttributes({ featured: value })}
                            help={__('Mark as featured video', 'tmu-theme')}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Video URLs', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Video URL', 'tmu-theme')}
                            value={attributes.video_url}
                            onChange={(value) => setAttributes({ video_url: value })}
                            placeholder={__('https://example.com/video.mp4', 'tmu-theme')}
                            help={validationErrors.video_url}
                        />
                        
                        <TextControl
                            label={__('Thumbnail URL', 'tmu-theme')}
                            value={attributes.thumbnail_url}
                            onChange={(value) => setAttributes({ thumbnail_url: value })}
                            placeholder={__('https://example.com/thumbnail.jpg', 'tmu-theme')}
                        />
                        
                        {attributes.thumbnail_url && (
                            <div className="thumbnail-preview mt-2">
                                <img 
                                    src={attributes.thumbnail_url}
                                    alt={attributes.title}
                                    style={{ maxWidth: '200px', height: 'auto' }}
                                />
                            </div>
                        )}
                    </PanelBody>
                    
                    <PanelBody title={__('Technical Specifications', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('Duration (seconds)', 'tmu-theme')}
                            value={attributes.duration}
                            onChange={(value) => setAttributes({ duration: parseInt(value) || 0 })}
                            min={0}
                            help={attributes.duration ? formatDuration(attributes.duration) : validationErrors.duration}
                        />
                        
                        <SelectControl
                            label={__('Quality', 'tmu-theme')}
                            value={attributes.quality}
                            options={[
                                { label: 'SD', value: 'SD' },
                                { label: 'HD', value: 'HD' },
                                { label: 'Full HD', value: 'FHD' },
                                { label: '4K', value: '4K' },
                                { label: '8K', value: '8K' },
                            ]}
                            onChange={(value) => setAttributes({ quality: value })}
                        />
                        
                        <TextControl
                            label={__('Resolution', 'tmu-theme')}
                            value={attributes.resolution}
                            onChange={(value) => setAttributes({ resolution: value })}
                            placeholder={__('1920x1080', 'tmu-theme')}
                        />
                        
                        <SelectControl
                            label={__('Format', 'tmu-theme')}
                            value={attributes.format}
                            options={[
                                { label: 'MP4', value: 'mp4' },
                                { label: 'AVI', value: 'avi' },
                                { label: 'MOV', value: 'mov' },
                                { label: 'WMV', value: 'wmv' },
                                { label: 'WebM', value: 'webm' },
                                { label: 'MKV', value: 'mkv' },
                            ]}
                            onChange={(value) => setAttributes({ format: value })}
                        />
                        
                        <NumberControl
                            label={__('File Size (bytes)', 'tmu-theme')}
                            value={attributes.file_size}
                            onChange={(value) => setAttributes({ file_size: parseInt(value) || 0 })}
                            min={0}
                            help={attributes.file_size ? formatFileSize(attributes.file_size) : ''}
                        />
                        
                        <NumberControl
                            label={__('Bitrate (kbps)', 'tmu-theme')}
                            value={attributes.bitrate}
                            onChange={(value) => setAttributes({ bitrate: parseInt(value) || 0 })}
                            min={0}
                        />
                        
                        <NumberControl
                            label={__('Frame Rate (fps)', 'tmu-theme')}
                            value={attributes.framerate}
                            onChange={(value) => setAttributes({ framerate: parseInt(value) || 24 })}
                            min={1}
                            max={120}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Audio & Video Codecs', 'tmu-theme')} initialOpen={false}>
                        <SelectControl
                            label={__('Video Codec', 'tmu-theme')}
                            value={attributes.video_codec}
                            options={[
                                { label: 'H.264', value: 'H.264' },
                                { label: 'H.265', value: 'H.265' },
                                { label: 'VP8', value: 'VP8' },
                                { label: 'VP9', value: 'VP9' },
                                { label: 'AV1', value: 'AV1' },
                                { label: 'MPEG-4', value: 'MPEG-4' },
                            ]}
                            onChange={(value) => setAttributes({ video_codec: value })}
                        />
                        
                        <SelectControl
                            label={__('Audio Codec', 'tmu-theme')}
                            value={attributes.audio_codec}
                            options={[
                                { label: 'AAC', value: 'AAC' },
                                { label: 'MP3', value: 'MP3' },
                                { label: 'Opus', value: 'Opus' },
                                { label: 'Vorbis', value: 'Vorbis' },
                                { label: 'FLAC', value: 'FLAC' },
                                { label: 'PCM', value: 'PCM' },
                            ]}
                            onChange={(value) => setAttributes({ audio_codec: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Language & Subtitles', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Language', 'tmu-theme')}
                            value={attributes.language}
                            onChange={(value) => setAttributes({ language: value })}
                            placeholder={__('English, Korean, etc.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Subtitles (JSON)', 'tmu-theme')}
                            value={JSON.stringify(attributes.subtitles)}
                            onChange={(value) => {
                                try {
                                    const parsed = JSON.parse(value);
                                    setAttributes({ subtitles: Array.isArray(parsed) ? parsed : [] });
                                } catch (e) {
                                    // Invalid JSON, ignore
                                }
                            }}
                            placeholder={__('["English", "Korean", "Spanish"]', 'tmu-theme')}
                            help={__('Enter available subtitles as JSON array', 'tmu-theme')}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Statistics', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Upload Date', 'tmu-theme')}
                            type="date"
                            value={attributes.upload_date}
                            onChange={(value) => setAttributes({ upload_date: value })}
                        />
                        
                        <NumberControl
                            label={__('Views', 'tmu-theme')}
                            value={attributes.views}
                            onChange={(value) => setAttributes({ views: parseInt(value) || 0 })}
                            min={0}
                        />
                        
                        <NumberControl
                            label={__('Likes', 'tmu-theme')}
                            value={attributes.likes}
                            onChange={(value) => setAttributes({ likes: parseInt(value) || 0 })}
                            min={0}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-video-metadata-block">
                    {attributes.title ? (
                        <div className="tmu-video-preview bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                            <div className="video-header mb-4">
                                <div className="flex items-center justify-between mb-2">
                                    <span className={`video-type-badge text-xs font-medium px-2.5 py-0.5 rounded ${
                                        attributes.video_type === 'trailer' ? 'bg-green-100 text-green-800' :
                                        attributes.video_type === 'teaser' ? 'bg-blue-100 text-blue-800' :
                                        attributes.video_type === 'behind_scenes' ? 'bg-purple-100 text-purple-800' :
                                        'bg-gray-100 text-gray-800'
                                    }`}>
                                        {attributes.video_type.replace('_', ' ').toUpperCase()}
                                    </span>
                                    {attributes.featured && (
                                        <span className="featured-badge bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                            ★ {__('Featured', 'tmu-theme')}
                                        </span>
                                    )}
                                </div>
                                <h3 className="video-title text-xl font-bold text-gray-900 mb-2">{attributes.title}</h3>
                            </div>
                            
                            <div className="video-content flex gap-4">
                                {attributes.thumbnail_url && (
                                    <div className="video-thumbnail flex-shrink-0">
                                        <img 
                                            src={attributes.thumbnail_url}
                                            alt={attributes.title}
                                            className="w-32 h-18 object-cover rounded-lg shadow-md"
                                        />
                                    </div>
                                )}
                                
                                <div className="video-details flex-1">
                                    <div className="video-meta grid grid-cols-2 gap-4 mb-4 text-sm">
                                        {attributes.duration > 0 && (
                                            <div className="meta-item">
                                                <span className="meta-label font-medium text-gray-600 block">{__('Duration:', 'tmu-theme')}</span>
                                                <span className="meta-value text-gray-900">{formatDuration(attributes.duration)}</span>
                                            </div>
                                        )}
                                        {attributes.quality && (
                                            <div className="meta-item">
                                                <span className="meta-label font-medium text-gray-600 block">{__('Quality:', 'tmu-theme')}</span>
                                                <span className="meta-value text-gray-900">{attributes.quality}</span>
                                            </div>
                                        )}
                                        {attributes.resolution && (
                                            <div className="meta-item">
                                                <span className="meta-label font-medium text-gray-600 block">{__('Resolution:', 'tmu-theme')}</span>
                                                <span className="meta-value text-gray-900">{attributes.resolution}</span>
                                            </div>
                                        )}
                                        {attributes.format && (
                                            <div className="meta-item">
                                                <span className="meta-label font-medium text-gray-600 block">{__('Format:', 'tmu-theme')}</span>
                                                <span className="meta-value text-gray-900">{attributes.format.toUpperCase()}</span>
                                            </div>
                                        )}
                                    </div>
                                    
                                    {attributes.description && (
                                        <div className="video-description">
                                            <h4 className="font-medium text-gray-700 mb-2">{__('Description:', 'tmu-theme')}</h4>
                                            <p className="text-gray-600 text-sm leading-relaxed">{attributes.description}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                            
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
                    ) : (
                        <Placeholder
                            icon="video-alt"
                            label={__('Video Metadata', 'tmu-theme')}
                            instructions={__('Configure video metadata in the block settings panel.', 'tmu-theme')}
                        />
                    )}
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        return (
            <div className="tmu-video-metadata">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
};

export default VideoMetadataBlock;