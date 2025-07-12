import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Search, X } from 'lucide-react';

interface SearchBarProps {
    onSearch: (query: string) => void;
    placeholder?: string;
    className?: string;
}

export default function SearchBar({ onSearch, placeholder = "議題を検索...", className = "" }: SearchBarProps) {
    const [query, setQuery] = useState('');
    const [isExpanded, setIsExpanded] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (query.trim()) {
            onSearch(query.trim());
        }
    };

    const handleClear = () => {
        setQuery('');
        setIsExpanded(false);
    };

    return (
        <div className={`relative ${className}`}>
            <form onSubmit={handleSubmit} className="flex items-center">
                <div className={`relative transition-all duration-200 ${isExpanded ? 'w-64' : 'w-48'}`}>
                    <input
                        type="text"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        onFocus={() => setIsExpanded(true)}
                        onBlur={() => !query && setIsExpanded(false)}
                        placeholder={placeholder}
                        className="w-full pl-10 pr-8 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500" />
                    {query && (
                        <button
                            type="button"
                            onClick={handleClear}
                            className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <X className="w-4 h-4" />
                        </button>
                    )}
                </div>
                <Button
                    type="submit"
                    size="sm"
                    className="ml-2 bg-blue-600 hover:bg-blue-700 text-white"
                    disabled={!query.trim()}
                >
                    検索
                </Button>
            </form>
        </div>
    );
}