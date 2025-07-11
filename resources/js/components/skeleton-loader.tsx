import React from 'react';
import { Card } from '@/components/ui/card';

interface SkeletonLoaderProps {
    count?: number;
    className?: string;
}

export function PostSkeleton({ className = '' }: { className?: string }) {
    return (
        <Card className={`bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 overflow-hidden p-0 ${className}`}>
            <div className="flex">
                {/* Topic Navigation Section Skeleton */}
                <div className="w-16 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 p-2 flex flex-col items-center">
                    <div className="skeleton w-8 h-6 rounded mb-2"></div>
                    <div className="skeleton w-8 h-6 rounded"></div>
                </div>

                {/* Main Content Skeleton */}
                <div className="flex-1 p-4">
                    {/* Header */}
                    <div className="flex items-center space-x-2 mb-3">
                        <div className="skeleton w-6 h-6 rounded-full"></div>
                        <div className="skeleton w-24 h-4 rounded"></div>
                        <div className="skeleton w-16 h-4 rounded"></div>
                        <div className="skeleton w-20 h-4 rounded"></div>
                    </div>

                    {/* Title */}
                    <div className="skeleton w-full h-6 rounded mb-3"></div>
                    <div className="skeleton w-3/4 h-6 rounded mb-3"></div>

                    {/* Content */}
                    <div className="space-y-2 mb-4">
                        <div className="skeleton w-full h-4 rounded"></div>
                        <div className="skeleton w-full h-4 rounded"></div>
                        <div className="skeleton w-2/3 h-4 rounded"></div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex items-center space-x-4">
                        <div className="skeleton w-16 h-8 rounded"></div>
                        <div className="skeleton w-16 h-8 rounded"></div>
                        <div className="skeleton w-20 h-8 rounded"></div>
                        <div className="skeleton w-16 h-8 rounded"></div>
                    </div>
                </div>

                {/* Voting Section Skeleton */}
                <div className="w-20 bg-gray-50 dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 p-3 flex flex-col items-center">
                    <div className="skeleton w-10 h-8 rounded mb-2"></div>
                    <div className="skeleton w-8 h-4 rounded mb-4"></div>
                    <div className="skeleton w-10 h-8 rounded"></div>
                </div>
            </div>
        </Card>
    );
}

export function SkeletonLoader({ count = 3, className = '' }: SkeletonLoaderProps) {
    return (
        <div className={`space-y-3 ${className}`}>
            {Array.from({ length: count }).map((_, index) => (
                <PostSkeleton key={`skeleton-${index}`} className="loading-separator" />
            ))}
        </div>
    );
}

// Loading Separator Component
export function LoadingSeparator() {
    return (
        <div className="loading-separator py-4 text-center">
            <div className="flex items-center justify-center space-x-2 text-gray-500 dark:text-gray-400">
                <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce"></div>
                <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }}></div>
                <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
                <span className="ml-2 text-sm">新しい投稿を読み込み中...</span>
            </div>
        </div>
    );
} 