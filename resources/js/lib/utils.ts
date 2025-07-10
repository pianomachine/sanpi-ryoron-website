import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/**
 * 現在のページURLを取得してログインURLに intended パラメータを追加
 */
export function getLoginUrlWithRedirect(currentUrl?: string): string {
  const redirectUrl = currentUrl || window.location.pathname + window.location.search;
  const encodedUrl = encodeURIComponent(redirectUrl);
  return `/login?intended=${encodedUrl}`;
}

/**
 * ログアウト用のデータを生成（現在のページにリダイレクトするため）
 */
export function getLogoutData(currentUrl?: string): { intended: string } {
  const redirectUrl = currentUrl || window.location.pathname + window.location.search;
  return { intended: redirectUrl };
}
