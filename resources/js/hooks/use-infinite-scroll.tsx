import { useState, useEffect, useCallback, useRef } from 'react';
import axios from 'axios';

interface InfiniteScrollOptions {
    url: string;
    initialData?: any[];
    params?: Record<string, any>;
    threshold?: number;
    enabled?: boolean;
}

interface InfiniteScrollReturn {
    data: any[];
    loading: boolean;
    hasMore: boolean;
    error: string | null;
    loadMore: () => void;
    refresh: () => void;
    currentPage: number;
    totalCount: number;
    newlyAddedItems: Set<string | number>;
    isItemNew: (id: string | number) => boolean;
    markItemAsOld: (id: string | number) => void;
    showingSkeleton: boolean;
}

export function useInfiniteScroll({
    url,
    initialData = [],
    params = {},
    threshold = 200,
    enabled = true
}: InfiniteScrollOptions): InfiniteScrollReturn {
    const [data, setData] = useState<any[]>(initialData);
    const [loading, setLoading] = useState(false);
    const [hasMore, setHasMore] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [totalCount, setTotalCount] = useState(0);
    const [newlyAddedItems, setNewlyAddedItems] = useState<Set<string | number>>(new Set());
    const [showingSkeleton, setShowingSkeleton] = useState(false);
    
    // 前回のデータサイズを追跡
    const previousDataSizeRef = useRef(initialData.length);

    const loadMore = useCallback(async () => {
        if (loading || !hasMore || !enabled) return;

        setLoading(true);
        setShowingSkeleton(true);
        setError(null);

        try {
            const response = await axios.get(url, {
                params: {
                    ...params,
                    page: currentPage + 1
                }
            });

            const newData = response.data.posts || [];
            const responseHasMore = response.data.has_more || false;
            const responseTotalCount = response.data.total_count || 0;

            // 新しく追加されるアイテムのIDを記録
            const newItemIds = new Set<string | number>();
            newData.forEach((item: any) => {
                if (item.id) {
                    newItemIds.add(item.id);
                }
            });

            setData(prevData => [...prevData, ...newData]);
            setNewlyAddedItems(newItemIds);
            setHasMore(responseHasMore);
            setCurrentPage(prevPage => prevPage + 1);
            setTotalCount(responseTotalCount);
            
            // アニメーション時間後にスケルトンを非表示
            setTimeout(() => {
                setShowingSkeleton(false);
            }, 800);
            
        } catch (err: any) {
            console.error('Failed to load more data:', err);
            setError(err.response?.data?.error || 'データの読み込みに失敗しました');
            setShowingSkeleton(false);
        } finally {
            setLoading(false);
        }
    }, [url, params, currentPage, loading, hasMore, enabled]);

    const refresh = useCallback(async () => {
        setLoading(true);
        setError(null);
        setCurrentPage(1);
        setHasMore(true);
        setNewlyAddedItems(new Set());
        previousDataSizeRef.current = 0;

        try {
            const response = await axios.get(url, {
                params: {
                    ...params,
                    page: 1
                }
            });

            const newData = response.data.posts || [];
            const responseHasMore = response.data.has_more || false;
            const responseTotalCount = response.data.total_count || 0;

            setData(newData);
            setHasMore(responseHasMore);
            setCurrentPage(1);
            setTotalCount(responseTotalCount);
            previousDataSizeRef.current = newData.length;
        } catch (err: any) {
            console.error('Failed to refresh data:', err);
            setError(err.response?.data?.error || 'データの読み込みに失敗しました');
        } finally {
            setLoading(false);
        }
    }, [url, params]);

    // アイテムが新しく追加されたかどうかをチェック
    const isItemNew = useCallback((id: string | number) => {
        return newlyAddedItems.has(id);
    }, [newlyAddedItems]);

    // アイテムを「古い」ものとしてマーク（アニメーション完了時に呼び出し）
    const markItemAsOld = useCallback((id: string | number) => {
        setNewlyAddedItems(prev => {
            const newSet = new Set(prev);
            newSet.delete(id);
            return newSet;
        });
    }, []);

    // スクロールイベントの処理
    useEffect(() => {
        if (!enabled) return;

        const handleScroll = () => {
            if (loading || !hasMore) return;

            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight;
            const clientHeight = window.innerHeight;

            if (scrollTop + clientHeight >= scrollHeight - threshold) {
                loadMore();
            }
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => window.removeEventListener('scroll', handleScroll);
    }, [loadMore, loading, hasMore, threshold, enabled]);

    // パラメータが変更された時のリフレッシュ
    useEffect(() => {
        if (enabled) {
            refresh();
        }
    }, [url, JSON.stringify(params), enabled]);

    return {
        data,
        loading,
        hasMore,
        error,
        loadMore,
        refresh,
        currentPage,
        totalCount,
        newlyAddedItems,
        isItemNew,
        markItemAsOld,
        showingSkeleton
    };
} 