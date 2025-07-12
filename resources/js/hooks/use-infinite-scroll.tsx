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
    threshold = 100, // モバイル向けにしきい値を調整
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
    
    // リクエストのキャンセル用
    const abortControllerRef = useRef<AbortController | null>(null);
    
    // 前回のパラメータを追跡
    const prevParamsRef = useRef(params);
    const prevUrlRef = useRef(url);
    
    // 前回のデータサイズを追跡
    const previousDataSizeRef = useRef(initialData.length);

    const isNearBottomRef = useRef(false);

    // リクエストをキャンセルする関数
    const cancelPreviousRequest = () => {
        if (abortControllerRef.current) {
            abortControllerRef.current.abort();
            abortControllerRef.current = null;
        }
    };

    const loadMore = useCallback(async () => {
        if (loading || !hasMore || !enabled) return;

        // 前のリクエストをキャンセル
        cancelPreviousRequest();
        
        // 新しいAbortControllerを作成
        abortControllerRef.current = new AbortController();
        
        setLoading(true);
        setShowingSkeleton(true);
        setError(null);

        try {
            const response = await axios.get(url, {
                params: {
                    ...params,
                    page: currentPage + 1
                },
                signal: abortControllerRef.current.signal
            });

            // リクエストがキャンセルされていない場合のみ状態を更新
            if (!abortControllerRef.current.signal.aborted) {
                const rawData = response.data.posts || [];
                const responseHasMore = response.data.has_more || false;
                const responseTotalCount = response.data.total_count || 0;

                // nullアイテムをフィルタリング
                const newData = rawData.filter((item: any) => item !== null && item !== undefined && item.id);

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
            }
        } catch (err: any) {
            // AbortErrorは無視
            if (err.name !== 'AbortError') {
                console.error('Failed to load more data:', err);
                setError(err.response?.data?.error || 'データの読み込みに失敗しました');
                setShowingSkeleton(false);
            }
        } finally {
            if (!abortControllerRef.current?.signal.aborted) {
                setLoading(false);
            }
        }
    }, [url, params, currentPage, loading, hasMore, enabled]);

    const refresh = useCallback(async () => {
        if (loading) return;
        
        // 前のリクエストをキャンセル
        cancelPreviousRequest();
        
        // 新しいAbortControllerを作成
        abortControllerRef.current = new AbortController();
        
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
                },
                signal: abortControllerRef.current.signal
            });

            // リクエストがキャンセルされていない場合のみ状態を更新
            if (!abortControllerRef.current.signal.aborted) {
                const rawData = response.data.posts || [];
                const responseHasMore = response.data.has_more || false;
                const responseTotalCount = response.data.total_count || 0;

                // nullアイテムをフィルタリング
                const newData = rawData.filter((item: any) => item !== null && item !== undefined && item.id);

                setData(newData);
                setHasMore(responseHasMore);
                setCurrentPage(1);
                setTotalCount(responseTotalCount);
                previousDataSizeRef.current = newData.length;
            }
        } catch (err: any) {
            // AbortErrorは無視
            if (err.name !== 'AbortError') {
                console.error('Failed to refresh data:', err);
                setError(err.response?.data?.error || 'データの読み込みに失敗しました');
            }
        } finally {
            if (!abortControllerRef.current?.signal.aborted) {
                setLoading(false);
            }
        }
    }, [url, params, loading]);

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

    // スクロールイベントの処理を改善
    useEffect(() => {
        if (!enabled) return;

        let scrollTimeout: NodeJS.Timeout;
        let lastScrollY = window.scrollY;
        let lastScrollTime = Date.now();

        const handleScroll = () => {
            if (loading || !hasMore) return;

            const now = Date.now();
            const timeDiff = now - lastScrollTime;

            // スクロール速度が速い場合はチェックを延期
            if (timeDiff < 50) {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(handleScroll, 50);
                return;
            }

            lastScrollTime = now;

            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollY = window.scrollY;
            const scrollDiff = Math.abs(scrollY - lastScrollY);

            // スクロール方向を考慮
            const isScrollingDown = scrollY > lastScrollY;
            lastScrollY = scrollY;

            // 高速スクロール時は早めに次のコンテンツを読み込む
            const dynamicThreshold = scrollDiff > 100 ? threshold * 2 : threshold;

            // スクロールが下向きで、かつ一定以上のスクロールがある場合のみチェック
            if (isScrollingDown && scrollDiff > 10) {
                const newIsNearBottom = (scrollY + windowHeight) >= (documentHeight - dynamicThreshold);

                if (newIsNearBottom && !isNearBottomRef.current) {
                    loadMore();
                }
                isNearBottomRef.current = newIsNearBottom;
            }
        };

        // パフォーマンス向上のためpassiveオプションを使用
        window.addEventListener('scroll', handleScroll, { passive: true });
        
        // タッチデバイス用のスクロール検知を追加
        window.addEventListener('touchmove', handleScroll, { passive: true });
        window.addEventListener('touchend', handleScroll, { passive: true });

        return () => {
            window.removeEventListener('scroll', handleScroll);
            window.removeEventListener('touchmove', handleScroll);
            window.removeEventListener('touchend', handleScroll);
            clearTimeout(scrollTimeout);
        };
    }, [loadMore, loading, hasMore, threshold, enabled]);

    // パラメータが変更された時のリフレッシュ
    useEffect(() => {
        const paramsChanged = JSON.stringify(prevParamsRef.current) !== JSON.stringify(params);
        const urlChanged = prevUrlRef.current !== url;
        
        if (enabled && (paramsChanged || urlChanged)) {
            prevParamsRef.current = params;
            prevUrlRef.current = url;
            refresh();
        }
    }, [url, params, enabled, refresh]);

    // コンポーネントのアンマウント時にリクエストをキャンセル
    useEffect(() => {
        return () => {
            cancelPreviousRequest();
        };
    }, []);

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