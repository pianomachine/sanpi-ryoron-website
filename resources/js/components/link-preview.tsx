import React from 'react';
import { ExternalLink } from 'lucide-react';

interface LinkPreview {
    url: string;
    title: string;
    description?: string;
    image?: string;
    domain: string;
    site_name?: string;
}

interface LinkPreviewProps {
    preview: LinkPreview;
    className?: string;
}

export default function LinkPreview({ preview, className = '' }: LinkPreviewProps) {
    const handleClick = () => {
        window.open(preview.url, '_blank', 'noopener,noreferrer');
    };

    return (
        <div 
            className={`border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-md transition-shadow cursor-pointer bg-white dark:bg-gray-800 ${className}`}
            onClick={handleClick}
        >
            <div className="flex">
                {/* Image section */}
                {preview.image && (
                    <div className="w-32 h-24 flex-shrink-0">
                        <img
                            src={preview.image}
                            alt={preview.title}
                            className="w-full h-full object-cover"
                            onError={(e) => {
                                const target = e.target as HTMLElement;
                                target.style.display = 'none';
                            }}
                        />
                    </div>
                )}
                
                {/* Content section */}
                <div className="flex-1 p-3 min-w-0">
                    {/* Domain */}
                    <div className="flex items-center text-xs text-gray-500 dark:text-gray-400 mb-1">
                        <ExternalLink className="w-3 h-3 mr-1" />
                        <span className="truncate">{preview.site_name || preview.domain}</span>
                    </div>
                    
                    {/* Title */}
                    <h3 className="font-medium text-sm text-gray-900 dark:text-white line-clamp-2 mb-1">
                        {preview.title}
                    </h3>
                    
                    {/* Description */}
                    {preview.description && (
                        <p className="text-xs text-gray-600 dark:text-gray-300 line-clamp-2">
                            {preview.description}
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}

interface LinkPreviewsProps {
    previews: LinkPreview[];
    className?: string;
}

export function LinkPreviews({ previews, className = '' }: LinkPreviewsProps) {
    console.log('LinkPreviews component - previews:', previews);
    
    if (!previews || previews.length === 0) {
        console.log('LinkPreviews component - no previews to display');
        return null;
    }

    console.log('LinkPreviews component - rendering', previews.length, 'previews');
    
    return (
        <div className={`space-y-3 ${className}`}>
            {previews.map((preview, index) => (
                <LinkPreview 
                    key={`${preview.url}-${index}`} 
                    preview={preview} 
                />
            ))}
        </div>
    );
}