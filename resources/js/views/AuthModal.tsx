import React, { useState } from 'react';
import api from '../services/api';
import { Lock, Mail, User as UserIcon, Shield, Sparkles } from 'lucide-react';
import { User } from '../types';

interface AuthModalProps {
    initialMode?: 'login' | 'register';
    onModeChange?: (mode: 'login' | 'register') => void;
    onLoginSuccess: (user: User, token: string) => void;
}

export const AuthModal: React.FC<AuthModalProps> = ({
    initialMode = 'login',
    onModeChange,
    onLoginSuccess,
}) => {
    const [isRegister, setIsRegister] = useState(initialMode === 'register');
    const [email, setEmail] = useState('admin@linkpilot.io');
    const [password, setPassword] = useState('password');
    const [name, setName] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    React.useEffect(() => {
        setIsRegister(initialMode === 'register');
        if (initialMode === 'register') {
            setEmail('');
            setPassword('');
        }
    }, [initialMode]);

    const handleSwitchToRegister = () => {
        setIsRegister(true);
        setEmail('');
        setPassword('');
        if (onModeChange) onModeChange('register');
    };

    const handleSwitchToLogin = () => {
        setIsRegister(false);
        setEmail('admin@linkpilot.io');
        setPassword('password');
        if (onModeChange) onModeChange('login');
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);
        setErrorMessage(null);

        try {
            if (isRegister) {
                const res = await api.post('/auth/register', {
                    name,
                    email,
                    password,
                    password_confirmation: passwordConfirmation,
                });
                localStorage.setItem('lp_token', res.data.token);
                onLoginSuccess(res.data.user, res.data.token);
            } else {
                const res = await api.post('/auth/login', { email, password });
                localStorage.setItem('lp_token', res.data.token);
                onLoginSuccess(res.data.user, res.data.token);
            }
        } catch (err: any) {
            setErrorMessage(err.response?.data?.message || err.message || 'Authentication failed.');
        } finally {
            setIsLoading(false);
        }
    };

    const fillDemoAdmin = () => {
        setEmail('admin@linkpilot.io');
        setPassword('password');
        setIsRegister(false);
    };

    const fillDemoSpecialist = () => {
        setEmail('specialist@linkpilot.io');
        setPassword('password');
        setIsRegister(false);
    };

    return (
        <div className="fixed inset-0 bg-slate-950/85 backdrop-blur-md z-50 flex items-center justify-center p-4">
            <div className="glass-panel p-8 rounded-3xl w-full max-w-md space-y-6 border border-slate-700/80 shadow-2xl relative overflow-hidden">
                {/* Glow accent */}
                <div className="absolute -top-16 -right-16 w-36 h-36 bg-indigo-500/20 rounded-full blur-2xl"></div>

                <div className="text-center space-y-1.5">
                    <h2 className="text-2xl font-black text-white tracking-tight flex items-center justify-center gap-2">
                        LinkPilot SEO
                    </h2>
                    <p className="text-xs text-slate-400">
                        {isRegister ? 'Create an enterprise workspace account' : 'Sign in to access search crawl & index monitor'}
                    </p>
                </div>

                {errorMessage && (
                    <div className="p-3 rounded-xl bg-rose-950/50 border border-rose-800 text-xs text-rose-300">
                        {errorMessage}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-3.5">
                    {isRegister && (
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Full Name</label>
                            <input
                                type="text"
                                required
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                className="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-100 focus:outline-none focus:border-indigo-500"
                                placeholder="Alex Morgan"
                            />
                        </div>
                    )}

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Work Email</label>
                        <input
                            type="email"
                            required
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-100 focus:outline-none focus:border-indigo-500"
                            placeholder="admin@linkpilot.io"
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-300 mb-1">Password</label>
                        <input
                            type="password"
                            required
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-100 focus:outline-none focus:border-indigo-500"
                            placeholder="••••••••"
                        />
                    </div>

                    {isRegister && (
                        <div>
                            <label className="block text-xs font-semibold text-slate-300 mb-1">Confirm Password</label>
                            <input
                                type="password"
                                required
                                value={passwordConfirmation}
                                onChange={(e) => setPasswordConfirmation(e.target.value)}
                                className="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-100 focus:outline-none focus:border-indigo-500"
                                placeholder="••••••••"
                            />
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md shadow-indigo-600/30 transition flex items-center justify-center gap-2 mt-4"
                    >
                        {isLoading ? 'Processing...' : isRegister ? 'Create Account' : 'Authenticate Workspace'}
                    </button>
                </form>

                {/* Quick 1-click Demo Fill */}
                <div className="pt-2 border-t border-slate-800/80">
                    <div className="text-[10px] text-slate-500 text-center uppercase tracking-wider mb-2 font-semibold">
                        Instant Demo Credentials
                    </div>
                    <div className="flex gap-2">
                        <button
                            type="button"
                            onClick={fillDemoAdmin}
                            className="flex-1 py-1.5 px-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-[11px] text-indigo-300 font-semibold border border-slate-700 text-center transition"
                        >
                            Admin Demo
                        </button>
                        <button
                            type="button"
                            onClick={fillDemoSpecialist}
                            className="flex-1 py-1.5 px-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 font-semibold border border-slate-700 text-center transition"
                        >
                            SEO Lead Demo
                        </button>
                    </div>
                </div>

                <div className="text-center text-xs text-slate-400">
                    {isRegister ? (
                        <span>
                            Already registered?{' '}
                            <a
                                href="/login"
                                onClick={(e) => {
                                    e.preventDefault();
                                    handleSwitchToLogin();
                                }}
                                className="text-indigo-400 hover:text-indigo-300 hover:underline font-semibold cursor-pointer"
                            >
                                Sign In
                            </a>
                        </span>
                    ) : (
                        <span>
                            Need an account?{' '}
                            <a
                                href="/register"
                                onClick={(e) => {
                                    e.preventDefault();
                                    handleSwitchToRegister();
                                }}
                                className="text-indigo-400 hover:text-indigo-300 hover:underline font-semibold cursor-pointer"
                            >
                                Register
                            </a>
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
};
